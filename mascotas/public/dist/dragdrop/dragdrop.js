class FileDragDrop {
    constructor(options = {}) {
        this.container = options.container || '.file-drop-container';
        this.fileInput = options.fileInput || '#file-input';
        this.form = options.form || null;
        this.allowedTypes = options.allowedTypes || [];
        this.maxFileSize = options.maxFileSize || null; // en bytes
        this.maxFiles = options.maxFiles || null;
        this.multiple = options.multiple || false;
        
        // Callbacks
        this.onFilesSelected = options.onFilesSelected || this.defaultFileHandler;
        this.onError = options.onError || this.defaultErrorHandler;
        this.onSubmit = options.onSubmit || null;
        this.onValidation = options.onValidation || null;
        
        // Estado interno
        this.selectedFiles = null;
        this.containerElement = null;
        this.fileInputElement = null;
        this.formElement = null;
        
        // Colores para estados
        this.colors = {
            default: '#ccc',
            hover: '#ea868f',
            dragover: '#4CAF50',
            error: '#f44336'
        };
        
        this.init();
    }
    
    init() {
        // Buscar elementos en el DOM
        this.containerElement = this.select(this.container);
        this.fileInputElement = this.select(this.fileInput);
        
        if (this.form) {
            this.formElement = this.select(this.form);
        }
        
        if (!this.containerElement || !this.fileInputElement) {
            throw new Error('Elementos requeridos no encontrados en el DOM');
        }
        
        // Configurar input file
        if (this.multiple) {
            this.fileInputElement.setAttribute('multiple', '');
        }
        
        // Establecer tipos permitidos
        if (this.allowedTypes.length > 0) {
            this.setFileInputAccept();
        }
        
        this.bindEvents();
    }
    
    bindEvents() {
        // Eventos de drag and drop
            this.containerElement.addEventListener(
                'dragover', (e) => this.handleDragOver(e)
            );
            this.containerElement.addEventListener(
                'dragleave', (e) => this.handleDragLeave(e)
            );
            this.containerElement.addEventListener(
                'drop', (e) => this.handleDrop(e)
            );
            this.containerElement.addEventListener(
                'click', (e) => this.handleContainerClick(e)
            );
        
        // Evento de selección de archivos
            this.fileInputElement.addEventListener(
                'change', (e) => this.handleFileInput(e)
            );
        
        // Evento de envío del formulario
            if (this.formElement) {
                this.formElement.addEventListener(
                    'submit', (e) => this.handleFormSubmit(e)
                );
            }
    }
    
    handleDragOver(e) {
        e.preventDefault();
        this.containerElement.style.borderColor = this.colors.dragover;
    }
    
    handleDragLeave(e) {
        e.preventDefault();
        this.containerElement.style.borderColor = this.colors.default;
    }
    
    handleDrop(e) {
        e.preventDefault();
        this.containerElement.style.borderColor = this.colors.default;
        
        const files = e.dataTransfer.files;
        this.processFiles(files);
    }
    
    handleContainerClick(e) {
        this.fileInputElement.click();
    }
    
    handleFileInput(e) {
        e.preventDefault();
        const files = e.target.files;
        this.processFiles(files);
    }
    
    handleFormSubmit(e) {
        e.preventDefault();
        if (this.onSubmit) {
            this.onSubmit(this.selectedFiles, e);
        }
    }
    
    processFiles(files) {
        if (!files || files.length === 0) {
            return;
        }
        
        const fileArray = Array.from(files);
        const validationResult = this.validateFiles(fileArray);
        
        if (!validationResult.isValid) {
            this.handleError(validationResult.errors);
            return;
        }
        
        this.selectedFiles = files;
        this.onFilesSelected(files);
    }
    
    validateFiles(files) {
        const errors = [];
        
        // Validar número máximo de archivos
        if (this.maxFiles && files.length > this.maxFiles) {
            errors.push(`Máximo ${this.maxFiles} archivos permitidos`);
        }
        
        // Validar cada archivo
        files.forEach((file, index) => {
            // Validar tipo de archivo
            if (this.allowedTypes.length > 0) {
                const isTypeAllowed = this.allowedTypes.some(type => {
                    if (type.startsWith('.')) {
                        return file.name.toLowerCase().endsWith(type.toLowerCase());
                    }
                    return file.type === type || file.type.startsWith(type.split('/')[0] + '/');
                });
                
                if (!isTypeAllowed) {
                    errors.push(`Archivo ${index + 1}: Tipo no permitido (${file.type})`);
                }
            }
            
            // Validar tamaño del archivo
            if (this.maxFileSize && file.size > this.maxFileSize) {
                errors.push(`Archivo ${index + 1}: Tamaño excede el límite (${this.formatFileSize(file.size)})`);
            }
        });
        
        // Validación personalizada
        if (this.onValidation) {
            const customErrors = this.onValidation(files);
            if (customErrors && customErrors.length > 0) {
                errors.push(...customErrors);
            }
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }
    
    handleError(errors) {
        this.containerElement.style.borderColor = this.colors.error;
        setTimeout(() => {
            this.containerElement.style.borderColor = this.colors.default;
        }, 2000);
        
        this.onError(errors);
    }
    
    defaultFileHandler(files) {
        console.log('Archivos seleccionados:', files);
        // Aquí puedes agregar lógica por defecto
    }
    
    defaultErrorHandler(errors) {
        console.error('Errores de validación:', errors);
        alert('Errores encontrados:\n' + errors.join('\n'));
    }
    
    // Métodos públicos
    getSelectedFiles() {
        return this.selectedFiles;
    }
    
    clearFiles() {
        this.selectedFiles = null;
        this.fileInputElement.value = '';
    }
    
    setAllowedTypes(types) {
        this.allowedTypes = types;
        this.setFileInputAccept();
    }
    
    setFileInputAccept() {
        if (this.allowedTypes.length === 0) {
            this.fileInputElement.removeAttribute('accept');
            return;
        }
        
        // Estrategia 1: Solo usar extensiones de archivo (más restrictivo)
        const extensionOnly = this.allowedTypes.filter(type => type.startsWith('.')).join(',');
        
        // Estrategia 2: Usar MIME types específicos sin wildcards
        const mimeTypesOnly = this.allowedTypes.filter(type => !type.startsWith('.') && !type.includes('*')).join(',');
        
        // Combinar ambas estrategias
        const acceptValue = [extensionOnly, mimeTypesOnly].filter(Boolean).join(',');
        
        if (acceptValue) {
            this.fileInputElement.setAttribute('accept', acceptValue);
        }
    }
    
    enable() {
        this.containerElement.style.pointerEvents = 'auto';
        this.containerElement.style.opacity = '1';
    }
    
    disable() {
        this.containerElement.style.pointerEvents = 'none';
        this.containerElement.style.opacity = '0.5';
    }
    
    updateText(newText) {
        const textElement = this.containerElement.querySelector('.file-drop-text');
        if (textElement) {
            textElement.textContent = newText;
        }
    }
    
    // Métodos utilitarios
    select(selector) {
        return document.querySelector(selector);
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Método para configurar tipos permitidos de forma más estricta
    setStrictFileTypes(config) {
        // Configuración más específica para minimizar opción "Todos los archivos"
        const options = {
            strategy: 'extensions', // 'extensions', 'mime', 'mixed'
            types: config.types || [],
            description: config.description || 'Archivos permitidos'
        };
        
        this.allowedTypes = options.types;
        
        switch (options.strategy) {
            case 'extensions':
                // Solo usar extensiones (más restrictivo)
                const extensions = options.types.filter(type => type.startsWith('.'));
                if (extensions.length > 0) {
                    this.fileInputElement.setAttribute('accept', extensions.join(','));
                }
                break;
                
            case 'mime':
                // Solo usar MIME types específicos (sin wildcards)
                const mimeTypes = options.types.filter(type => !type.startsWith('.') && !type.includes('*'));
                if (mimeTypes.length > 0) {
                    this.fileInputElement.setAttribute('accept', mimeTypes.join(','));
                }
                break;
                
            case 'mixed':
            default:
                this.setFileInputAccept();
                break;
        }
    }
    
    destroy() {
        // Remover event listeners
        this.containerElement.removeEventListener('dragover', this.handleDragOver);
        this.containerElement.removeEventListener('dragleave', this.handleDragLeave);
        this.containerElement.removeEventListener('drop', this.handleDrop);
        this.containerElement.removeEventListener('click', this.handleContainerClick);
        this.fileInputElement.removeEventListener('change', this.handleFileInput);
        
        if (this.formElement) {
            this.formElement.removeEventListener('submit', this.handleFormSubmit);
        }
        
        // Limpiar referencias
        this.selectedFiles = null;
        this.containerElement = null;
        this.fileInputElement = null;
        this.formElement = null;
    }
}
class Form_Validator {
    constructor() {
        this.rules = {};
        this.errors = {};
        this.customValidators = {};
        this.messages = {
            // Mensajes por defecto en español
            required: 'Este campo es obligatorio',
            email: 'Debe ser un email válido',
            min: 'Debe tener al menos {min} caracteres',
            max: 'No debe exceder {max} caracteres',
            minLength: 'Debe tener al menos {min} caracteres',
            maxLength: 'No debe exceder {max} caracteres',
            minValue: 'El valor debe ser mayor o igual a {min}',
            maxValue: 'El valor debe ser menor o igual a {max}',
            pattern: 'El formato no es válido',
            numeric: 'Debe ser un número válido',
            integer: 'Debe ser un número entero',
            decimal: 'Debe ser un número decimal válido',
            alpha: 'Solo se permiten letras',
            alphanumeric: 'Solo se permiten letras y números',
            text: 'Solo se permiten letras, signos de puntuación y números',
            url: 'Debe ser una URL válida',
            date: 'Debe ser una fecha válida',
            time: 'Debe ser una hora válida',
            datetime: 'Debe ser una fecha y hora válida',
            phone: 'Debe ser un número de teléfono válido',
            creditCard: 'Debe ser un número de tarjeta de crédito válido',
            strongPassword: 'La contraseña debe ser más segura',
            confirmed: 'Los campos no coinciden',
            unique: 'Este valor ya existe',
            fileSize: 'El archivo excede el tamaño máximo de {max}MB',
            fileType: 'Tipo de archivo no permitido',
            in: 'Debe seleccionar una opción válida',
            notIn: 'El valor seleccionado no es válido',
            between: 'El valor debe estar entre {min} y {max}',
            boolean: 'Debe ser verdadero o falso',
            json: 'Debe ser un JSON válido',
            base64: 'Debe ser una cadena Base64 válida',
            ipAddress: 'Debe ser una dirección IP válida',
            macAddress: 'Debe ser una dirección MAC válida',
            uuid: 'Debe ser un UUID válido',
            iban: 'Debe ser un IBAN válido',
            isbn: 'Debe ser un ISBN válido',
            postalCode: 'Debe ser un código postal válido',
            slug: 'Debe ser un slug válido (solo letras, números y guiones)',
            timezone: 'Debe ser una zona horaria válida'
        };
        
        // Patrones RegExp predefinidos
        this.patterns = {
            email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
            phone: /^[\+]?[1-9][\d]{0,15}$/,
            url: /^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)$/,
            alpha: /^[a-zA-ZÀ-ÿ\s]+$/,
            alphanumeric: /^[a-zA-Z0-9À-ÿ\s]+$/,
            text: /^[a-zA-Z0-9\s.,;:!?()"'¿¡\-]*$/,
            numeric: /^-?\d*\.?\d+$/,
            integer: /^-?\d+$/,
            decimal: /^-?\d*\.\d+$/,
            strongPassword: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
            creditCard: /^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|3[0-9]{13}|6(?:011|5[0-9]{2})[0-9]{12})$/,
            base64: /^[A-Za-z0-9+/]*={0,2}$/,
            ipAddress: /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/,
            ipv6: /^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))$/,
            macAddress: /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/,
            uuid: /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i,
            slug: /^[a-z0-9]+(?:-[a-z0-9]+)*$/,
            postalCode: {
                US: /^\d{5}(-\d{4})?$/,
                CA: /^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$/,
                UK: /^[A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}$/,
                ES: /^\d{5}$/,
                FR: /^\d{5}$/,
                DE: /^\d{5}$/,
                general: /^[A-Za-z0-9\s-]{3,10}$/
            }
        };
    }

    // Definir reglas para un campo
    addRule(fieldName, rules) {
        this.rules[fieldName] = rules;
        return this;
    }

    // Definir múltiples reglas
    addRules(rulesObject) {
        Object.assign(this.rules, rulesObject);
        return this;
    }

    // Agregar validador personalizado
    addCustomValidator(name, validator, message) {
        this.customValidators[name] = validator;
        if (message) {
            this.messages[name] = message;
        }
        return this;
    }

    // Personalizar mensajes
    setMessages(messages) {
        Object.assign(this.messages, messages);
        return this;
    }

    // Validar un solo campo
    validateField(fieldName, value, rules = null) {
        const fieldRules = rules || this.rules[fieldName];
        if (!fieldRules) return true;

        const errors = [];

        // Convertir reglas a array si es string
        const rulesArray = typeof fieldRules === 'string' 
            ? fieldRules.split('|') 
            : Array.isArray(fieldRules) 
                ? fieldRules 
                : [fieldRules];

        for (let rule of rulesArray) {
            const error = this.applyRule(fieldName, value, rule);
            if (error) {
                errors.push(error);
            }
        }

        if (errors.length > 0) {
            this.errors[fieldName] = errors;
            return false;
        }

        delete this.errors[fieldName];
        return true;
    }

    // Aplicar una regla específica
    applyRule(fieldName, value, rule) {
        // Si es un objeto con regla y parámetros
        if (typeof rule === 'object' && rule.rule) {
            return this.executeRule(fieldName, value, rule.rule, rule.params || {}, rule.message);
        }

        // Si es una función personalizada
        if (typeof rule === 'function') {
            const result = rule(value, fieldName);
            return result === true ? null : (result || this.messages.custom || 'Valor inválido');
        }

        // Si es string con parámetros (ej: "min:5")
        if (typeof rule === 'string') {
            const [ruleName, ...params] = rule.split(':');
            const ruleParams = params.length > 0 ? params.join(':').split(',') : [];
            return this.executeRule(fieldName, value, ruleName, ruleParams);
        }

        return null;
    }

    // Ejecutar regla específica
    executeRule(fieldName, value, ruleName, params = [], customMessage = null) {
        // Valor vacío solo falla en required
        if ((value === null || value === undefined || value === '') && ruleName !== 'required') {
            return null;
        }

        const ruleMethod = `validate${ruleName.charAt(0).toUpperCase() + ruleName.slice(1)}`;
        
        if (typeof this[ruleMethod] === 'function') {
            const isValid = this[ruleMethod](value, params, fieldName);
            if (!isValid) {
                return customMessage || this.formatMessage(ruleName, params);
            }
        } else if (this.customValidators[ruleName]) {
            const isValid = this.customValidators[ruleName](value, params, fieldName);
            if (!isValid) {
                return customMessage || this.messages[ruleName] || 'Valor inválido';
            }
        }

        return null;
    }

    // Formatear mensaje con parámetros
    formatMessage(ruleName, params) {
        let message = this.messages[ruleName] || 'Valor inválido';
        
        if (Array.isArray(params)) {
            params.forEach((param, index) => {
                message = message.replace(`{${index}}`, param);
                message = message.replace(`{min}`, params[0]);
                message = message.replace(`{max}`, params[1] || params[0]);
            });
        }

        return message;
    }

    // REGLAS DE VALIDACIÓN IMPLEMENTADAS

    validateRequired(value) {
        if (Array.isArray(value)) return value.length > 0;
        if (typeof value === 'object' && value !== null) return Object.keys(value).length > 0;
        return value !== null && value !== undefined && String(value).trim() !== '';
    }

    validateEmail(value) {
        return this.patterns.email.test(value);
    }

    validateMin(value, params) {
        const min = parseInt(params[0]);
        return String(value).length >= min;
    }

    validateMax(value, params) {
        const max = parseInt(params[0]);
        return String(value).length <= max;
    }

    validateMinLength(value, params) {
        return this.validateMin(value, params);
    }

    validateMaxLength(value, params) {
        return this.validateMax(value, params);
    }

    validateMinValue(value, params) {
        const min = parseFloat(params[0]);
        const numValue = parseFloat(value);
        return !isNaN(numValue) && numValue >= min;
    }

    validateMaxValue(value, params) {
        const max = parseFloat(params[0]);
        const numValue = parseFloat(value);
        return !isNaN(numValue) && numValue <= max;
    }

    validateBetween(value, params) {
        const min = parseFloat(params[0]);
        const max = parseFloat(params[1]);
        const numValue = parseFloat(value);
        return !isNaN(numValue) && numValue >= min && numValue <= max;
    }

    validatePattern(value, params) {
        const pattern = new RegExp(params[0]);
        return pattern.test(value);
    }

    validateNumeric(value) {
        return this.patterns.numeric.test(value);
    }

    validateInteger(value) {
        return this.patterns.integer.test(value);
    }

    validateDecimal(value) {
        return this.patterns.decimal.test(value);
    }

    validateAlpha(value) {
        return this.patterns.alpha.test(value);
    }

    validateAlphanumeric(value) {
        return this.patterns.alphanumeric.test(value);
    }

    validateText(value) {
        return this.patterns.text.test(value);
    }

    validateUrl(value) {
        return this.patterns.url.test(value);
    }

    validatePhone(value) {
        return this.patterns.phone.test(value);
    }

    validateCreditCard(value) {
        return this.patterns.creditCard.test(value);
    }

    validateStrongPassword(value) {
        return this.patterns.strongPassword.test(value);
    }

    validateDate(value) {
        const date = new Date(value);
        return date instanceof Date && !isNaN(date);
    }

    validateTime(value) {
        return /^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/.test(value);
    }

    validateDatetime(value) {
        return this.validateDate(value);
    }

    validateBoolean(value) {
        return value === true || value === false || value === 'true' || value === 'false' || value === 1 || value === 0;
    }

    validateIn(value, params) {
        return params.includes(String(value));
    }

    validateNotIn(value, params) {
        return !params.includes(String(value));
    }

    validateConfirmed(value, params, fieldName) {
        const confirmField = params[0] || `${fieldName}_confirmation`;
        const confirmValue = document.getElementById(confirmField)?.value;
        return value === confirmValue;
    }

    validateJson(value) {
        try {
            JSON.parse(value);
            return true;
        } catch {
            return false;
        }
    }

    validateBase64(value) {
        return this.patterns.base64.test(value);
    }

    validateIpAddress(value) {
        return this.patterns.ipAddress.test(value) || this.patterns.ipv6.test(value);
    }

    validateMacAddress(value) {
        return this.patterns.macAddress.test(value);
    }

    validateUuid(value) {
        return this.patterns.uuid.test(value);
    }

    validateSlug(value) {
        return this.patterns.slug.test(value);
    }

    validatePostalCode(value, params) {
        const country = params[0] || 'general';
        const pattern = this.patterns.postalCode[country] || this.patterns.postalCode.general;
        return pattern.test(value);
    }

    validateFileSize(file, params) {
        if (!(file instanceof File)) return false;
        const maxSize = parseInt(params[0]) * 1024 * 1024; // MB to bytes
        return file.size <= maxSize;
    }

    validateFileType(file, params) {
        if (!(file instanceof File)) return false;
        const allowedTypes = params;
        return allowedTypes.includes(file.type) || allowedTypes.some(type => file.name.endsWith(type));
    }

    // Validar formulario completo
    validate(formData, rulesObject = null) {
        this.errors = {};
        const rules = rulesObject || this.rules;
        let isValid = true;

        for (const [fieldName, fieldRules] of Object.entries(rules)) {
            const value = formData[fieldName];
            if (!this.validateField(fieldName, value, fieldRules)) {
                isValid = false;
            }
        }

        return isValid;
    }

    // Validar formulario DOM
    validateForm(formElement) {
        const formData = new FormData(formElement);
        const data = Object.fromEntries(formData.entries());
        
        // Manejar checkboxes y radios
        const checkboxes = formElement.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => {
            if (cb.name && !data[cb.name]) {
                data[cb.name] = cb.checked;
            }
        });

        // Manejar archivos
        const fileInputs = formElement.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            if (input.files.length > 0) {
                data[input.name] = input.files[0];
            }
        });

        return this.validate(data);
    }

    // Obtener errores
    getErrors() {
        return this.errors;
    }

    // Obtener errores de un campo específico
    getFieldErrors(fieldName) {
        return this.errors[fieldName] || [];
    }

    // Verificar si hay errores
    hasErrors() {
        return Object.keys(this.errors).length > 0;
    }

    // Limpiar errores
    clearErrors(fieldName = null) {
        if (fieldName) {
            delete this.errors[fieldName];
        } else {
            this.errors = {};
        }
        return this;
    }

    // Validación en tiempo real
    attachRealTimeValidation(formElement) {
        const inputs = formElement.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            const fieldName = input.name;
            if (!fieldName || !this.rules[fieldName]) return;

            input.addEventListener('blur', () => {
                this.validateField(fieldName, input.value);
                this.displayFieldErrors(input, fieldName);
            });

            input.addEventListener('input', () => {
                // Limpiar errores mientras el usuario escribe
                if (this.errors[fieldName]) {
                    this.clearErrors(fieldName);
                    this.clearFieldErrorDisplay(input);
                }
            });
        });
    }

    // Mostrar errores en el DOM
    displayFieldErrors(inputElement, fieldName) {
        this.clearFieldErrorDisplay(inputElement);
        
        const errors = this.getFieldErrors(fieldName);
        if (errors.length > 0) {
            inputElement.classList.add('error');
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = errors[0]; // Mostrar solo el primer error
            
            inputElement.parentNode.insertBefore(errorDiv, inputElement.nextSibling);
        } else {
            inputElement.classList.remove('error');
        }
    }

    clearFieldErrorDisplay(inputElement) {
        inputElement.classList.remove('error');
        const errorMsg = inputElement.parentNode.querySelector('.error-message');
        if (errorMsg) {
            errorMsg.remove();
        }
    }

    // Mostrar todos los errores en el formulario después de la validación
    displayAllFieldErrors(formElement = null) {
        if (!formElement) {
            console.warn('Se requiere el elemento form para mostrar los errores');
            return this;
        }

        // Limpiar errores previos
        this.clearAllFieldErrors(formElement);

        // Mostrar nuevos errores
        Object.keys(this.errors).forEach(fieldName => {
            const inputElement = formElement.querySelector(`[name="${fieldName}"]`);
            if (inputElement) {
                this.displayFieldErrors(inputElement, fieldName);
            }
        });

        // Hacer scroll al primer error
        this.scrollToFirstError(formElement);

        return this;
    }

    // Limpiar todos los errores del formulario
    clearAllFieldErrors(formElement) {
        const inputs = formElement.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            this.clearFieldErrorDisplay(input);
        });
        return this;
    }

    // Hacer scroll al primer campo con error
    scrollToFirstError(formElement) {
        const firstErrorField = formElement.querySelector('.error');
        if (firstErrorField) {
            firstErrorField.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            firstErrorField.focus();
        }
        return this;
    }

    // Mostrar errores en un contenedor específico (alternativa)
    displayErrorsInContainer(containerId, options = {}) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.warn(`Contenedor con ID "${containerId}" no encontrado`);
            return this;
        }

        const defaultOptions = {
            showAsList: true,
            showFieldNames: true,
            className: 'validation-errors',
            clearPrevious: true
        };

        const config = { ...defaultOptions, ...options };

        if (config.clearPrevious) {
            container.innerHTML = '';
        }

        if (!this.hasErrors()) {
            return this;
        }

        const errorContainer = document.createElement('div');
        errorContainer.className = config.className;

        if (config.showAsList) {
            const errorList = document.createElement('ul');
            errorList.className = 'error-list';

            Object.entries(this.errors).forEach(([fieldName, errors]) => {
                errors.forEach(error => {
                    const listItem = document.createElement('li');
                    listItem.className = 'error-item';
                    listItem.innerHTML = config.showFieldNames 
                        ? `<strong>${this.getFieldLabel(fieldName)}:</strong> ${error}`
                        : error;
                    errorList.appendChild(listItem);
                });
            });

            errorContainer.appendChild(errorList);
        } else {
            Object.entries(this.errors).forEach(([fieldName, errors]) => {
                const fieldDiv = document.createElement('div');
                fieldDiv.className = 'field-errors';
                
                if (config.showFieldNames) {
                    const fieldLabel = document.createElement('strong');
                    fieldLabel.textContent = this.getFieldLabel(fieldName) + ':';
                    fieldDiv.appendChild(fieldLabel);
                }

                errors.forEach(error => {
                    const errorSpan = document.createElement('span');
                    errorSpan.className = 'error-text';
                    errorSpan.textContent = error;
                    fieldDiv.appendChild(errorSpan);
                });

                errorContainer.appendChild(fieldDiv);
            });
        }

        container.appendChild(errorContainer);
        return this;
    }

    // Obtener etiqueta del campo (intenta encontrar el label asociado)
    getFieldLabel(fieldName) {
        const input = document.querySelector(`[name="${fieldName}"]`);
        if (input) {
            // Buscar label por 'for' attribute
            const label = document.querySelector(`label[for="${input.id}"]`);
            if (label) {
                return label.textContent.trim().replace(':', '');
            }

            // Buscar label padre
            const parentLabel = input.closest('label');
            if (parentLabel) {
                return parentLabel.textContent.replace(input.value || '', '').trim().replace(':', '');
            }

            // Buscar por placeholder
            if (input.placeholder) {
                return input.placeholder;
            }

            // Usar data-label si existe
            if (input.dataset.label) {
                return input.dataset.label;
            }
        }

        // Formatear nombre del campo como último recurso
        return fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    // Mostrar errores con toast/notificación
    displayErrorsAsToast(options = {}) {
        const defaultOptions = {
            position: 'top-right',
            duration: 5000,
            className: 'validation-toast',
            showFieldNames: true
        };

        const config = { ...defaultOptions, ...options };

        if (!this.hasErrors()) {
            return this;
        }

        // Crear toast container si no existe
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = `toast-container ${config.position}`;
            toastContainer.style.cssText = `
                position: fixed;
                z-index: 10000;
                pointer-events: none;
            `;
            
            // Posicionar según configuración
            switch(config.position) {
                case 'top-right':
                    toastContainer.style.top = '20px';
                    toastContainer.style.right = '20px';
                    break;
                case 'top-left':
                    toastContainer.style.top = '20px';
                    toastContainer.style.left = '20px';
                    break;
                case 'bottom-right':
                    toastContainer.style.bottom = '20px';
                    toastContainer.style.right = '20px';
                    break;
                case 'bottom-left':
                    toastContainer.style.bottom = '20px';
                    toastContainer.style.left = '20px';
                    break;
            }
            
            document.body.appendChild(toastContainer);
        }

        // Crear toast
        const toast = document.createElement('div');
        toast.className = config.className;
        toast.style.cssText = `
            background: #f44336;
            color: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            pointer-events: auto;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            max-width: 350px;
        `;

        // Contenido del toast
        const errorCount = Object.keys(this.errors).length;
        const title = document.createElement('div');
        title.style.fontWeight = 'bold';
        title.style.marginBottom = '5px';
        title.textContent = `${errorCount} error${errorCount > 1 ? 'es' : ''} de validación`;
        toast.appendChild(title);

        const errorList = document.createElement('ul');
        errorList.style.cssText = 'margin: 0; padding-left: 20px; font-size: 14px;';
        
        Object.entries(this.errors).forEach(([fieldName, errors]) => {
            errors.slice(0, 1).forEach(error => { // Solo mostrar el primer error por campo
                const listItem = document.createElement('li');
                listItem.textContent = config.showFieldNames 
                    ? `${this.getFieldLabel(fieldName)}: ${error}`
                    : error;
                errorList.appendChild(listItem);
            });
        });

        toast.appendChild(errorList);
        toastContainer.appendChild(toast);

        // Animar entrada
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        }, 10);

        // Auto-remover
        if (config.duration > 0) {
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, config.duration);
        }

        // Permitir cerrar manualmente
        toast.addEventListener('click', () => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        });

        return this;
    }

    // Método mejorado de validación que incluye opción de mostrar errores automáticamente
    validateWithDisplay(formData, options = {}) {
        const defaultOptions = {
            showErrors: true,
            errorDisplay: 'inline', // 'inline', 'container', 'toast'
            formElement: null,
            containerId: null,
            scrollToError: true
        };

        const config = { ...defaultOptions, ...options };
        const isValid = this.validate(formData);

        if (!isValid && config.showErrors) {
            switch(config.errorDisplay) {
                case 'inline':
                    if (config.formElement) {
                        this.displayAllFieldErrors(config.formElement);
                    }
                    break;
                case 'container':
                    if (config.containerId) {
                        this.displayErrorsInContainer(config.containerId);
                    }
                    break;
                case 'toast':
                    this.displayErrorsAsToast(config.toastOptions || {});
                    break;
            }
        }

        return isValid;
    }

    // Método simplificado para validar formulario DOM con display automático
    validateFormWithDisplay(formElement, options = {}) {
        const formData = new FormData(formElement);
        const data = Object.fromEntries(formData.entries());
        
        // Manejar checkboxes y radios
        const checkboxes = formElement.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => {
            if (cb.name && !data[cb.name]) {
                data[cb.name] = cb.checked;
            }
        });

        // Manejar archivos
        const fileInputs = formElement.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            if (input.files.length > 0) {
                data[input.name] = input.files[0];
            }
        });

        return this.validateWithDisplay(data, {
            ...options,
            formElement: formElement
        });
    }
}
/*
    // EJEMPLO DE USO COMPLETO CON DISPLAY DE ERRORES
    const validator = new FormValidator();

    // Definir reglas
    validator.addRules({
        nombre: ['required', 'min:2', 'max:50', 'alpha'],
        email: ['required', 'email'],
        edad: ['required', 'integer', 'minValue:18', 'maxValue:100'],
        password: ['required', 'strongPassword'],
        password_confirmation: ['required', 'confirmed:password'],
        telefono: ['phone'],
        website: ['url']
    });

    // MÉTODO 1: Validar y mostrar errores inline automáticamente
    const formElement = document.getElementById('miFormulario');
    if (!validator.validateFormWithDisplay(formElement)) {
        console.log('Formulario inválido - errores mostrados inline');
    }

    // MÉTODO 2: Validar manualmente y luego mostrar errores
    const formData = {
        nombre: '',
        email: 'email-invalido',
        edad: 15
    };

    if (!validator.validate(formData)) {
        // Mostrar errores inline en el formulario
        validator.displayAllFieldErrors(formElement);
        
        // O mostrar en un contenedor específico
        validator.displayErrorsInContainer('error-container', {
            showFieldNames: true,
            showAsList: true
        });
        
        // O mostrar como toast/notificación
        validator.displayErrorsAsToast({
            position: 'top-right',
            duration: 5000
        });
    }

    // MÉTODO 3: Uso con opciones avanzadas
    validator.validateWithDisplay(formData, {
        showErrors: true,
        errorDisplay: 'inline', // 'inline', 'container', 'toast'
        formElement: formElement,
        scrollToError: true
    });

    // EJEMPLO CON EVENT LISTENERS
    document.getElementById('miFormulario').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validator.validateFormWithDisplay(this)) {
            console.log('¡Formulario válido! Enviando...');
            // Procesar formulario
        } else {
            console.log('Formulario inválido, errores mostrados automáticamente');
        }
    });
*/
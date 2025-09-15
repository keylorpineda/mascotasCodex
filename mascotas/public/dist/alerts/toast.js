window.alerta = {
    _notificacion: null,
    _idtoast: "#toast",
    _mensaje: null,
    _titulo: null,
    _icono: null,
    _tipo: null,

    // Verificar dependencias al inicializar
    _checkDependencies: function() {
        const errors = [];
        
        if (typeof document === 'undefined' || !document.querySelector) {
            errors.push('Document.querySelector no está disponible');
        }
        
        if (typeof bootstrap === 'undefined' || !bootstrap.Toast) {
            errors.push('Bootstrap Toast no está disponible');
        }
        
        if (errors.length > 0) {
            console.error('Dependencias faltantes:', errors);
            return false;
        }
        
        return true;
    },

    // Función auxiliar para verificar si un elemento existe
    _elementExists: function(selector) {
        return document.querySelector(selector) !== null;
    },

    // Función auxiliar para obtener elemento (reemplaza select())
    _getElement: function(selector) {
        return document.querySelector(selector);
    },

    // Función auxiliar para verificar si una variable está definida y no es null
    _isSet: function(variable) {
        return variable !== undefined && variable !== null;
    },

    show: function(idToast = null, ...parametros) {
        if (!this._checkDependencies()) {
            return false;
        }

        idToast = idToast || this._idtoast;
        
        if (!this._elementExists(idToast)) {
            console.error(`Elemento toast con selector "${idToast}" no encontrado`);
            return false;
        }

        const notificacion = this._notificacion = this._getElement(idToast);
        
        if (parametros.length > 0) {
            const { mensaje, titulo, tipo, icono } = parametros[0];
            
            if (!this._showNotification(tipo, mensaje, titulo)) {
                return false;
            }
            
            if (icono != null && icono.toUpperCase() in this.iconos) {
                this._icono = this.iconos[icono.toUpperCase()];
            }
        }
        
        if (!this._setAtributosEnNotificacion()) {
            console.error("Una o más propiedades requeridas para mostrar el mensaje de alerta son nulas");
            return false;
        }
        
        try {
            const toast = new bootstrap.Toast(this._notificacion, { delay: 8000 });
            const onShown = () => {
                this._onToastShown(notificacion);
                notificacion.removeEventListener("shown.bs.toast", onShown);
            };
            
            notificacion.addEventListener('shown.bs.toast', onShown);
            toast.show();
            return true;
        } catch (error) {
            console.error('Error al mostrar toast:', error);
            return false;
        }
    },

    _onToastShown: function(notificacion) {
        notificacion.classList.remove("fade");
    },

    iconos: {
        DANGER: 'fa fa-exclamation-circle text-danger',
        WARNING: 'fa fa-exclamation-triangle text-warning',
        SUCCESS: 'fa fa-check-circle text-success',
        INFORMATION: 'fa fa-info-circle text-info',
    },
    
    tipos: {
        DANGER: 'bg-danger',
        WARNING: 'bg-warning',
        SUCCESS: 'bg-success',
        INFORMATION: 'bg-info',
    },

    titulosDefault: {
        DANGER: 'Error',
        WARNING: 'Atención!',
        SUCCESS: 'Correcto',
        INFORMATION: 'Atención',
    },

    // Método unificado para mostrar notificaciones
    _showNotification: function(tipo, mensaje, titulo = null) {
        if (!tipo || !mensaje) {
            console.error('Tipo y mensaje son requeridos');
            return false;
        }

        const tipoUpper = tipo.toUpperCase();
        
        if (!(tipoUpper in this.tipos)) {
            console.error(`Tipo "${tipo}" no válido. Tipos válidos: ${Object.keys(this.tipos).join(', ')}`);
            return false;
        }

        this._mensaje = mensaje;
        this._titulo = titulo || this.titulosDefault[tipoUpper];
        this._icono = this.iconos[tipoUpper];
        this._tipo = this.tipos[tipoUpper];

        if (this._isSet(this._idtoast) && this._elementExists(this._idtoast)) {
            return this.show(this._idtoast);
        }

        return true;
    },

    // Métodos públicos simplificados
    success: function(mensaje, titulo = null) {
        const result = this._showNotification('SUCCESS', mensaje, titulo);
        return result ? this : null;
    },

    warning: function(mensaje, titulo = null) {
        const result = this._showNotification('WARNING', mensaje, titulo);
        return result ? this : null;
    },

    danger: function(mensaje, titulo = null) {
        const result = this._showNotification('DANGER', mensaje, titulo);
        return result ? this : null;
    },

    info: function(mensaje, titulo = null) {
        const result = this._showNotification('INFORMATION', mensaje, titulo);
        return result ? this : null;
    },

    // Métodos para compatibilidad con nombres anteriores (opcional)
    Success: function(mensaje, titulo = null) {
        console.warn('Success() está deprecated, usa success()');
        return this.success(mensaje, titulo);
    },

    Warning: function(mensaje, titulo = null) {
        console.warn('Warning() está deprecated, usa warning()');
        return this.warning(mensaje, titulo);
    },

    Danger: function(mensaje, titulo = null) {
        console.warn('Danger() está deprecated, usa danger()');
        return this.danger(mensaje, titulo);
    },

    Info: function(mensaje, titulo = null) {
        console.warn('Info() está deprecated, usa info()');
        return this.info(mensaje, titulo);
    },

    _setIconoClaseBase: function() {
        const icono = this._notificacion.querySelector(".toast-header i");
        if (!icono) {
            console.error('Elemento icono no encontrado en toast-header');
            return false;
        }
        
        icono.className = "text-white fa fa-lg";
        return true;
    },

    _setTipoClaseBase: function() {
        const header = this._notificacion.querySelector(".toast-header");
        if (!header) {
            console.error('Elemento toast-header no encontrado');
            return false;
        }
        
        header.className = "toast-header";
        return true;
    },

    _setAtributosEnNotificacion: function() {
        if (!this._setTipoClaseBase() || !this._setIconoClaseBase()) {
            return false;
        }

        if (!this._isSet(this._tipo) || !this._isSet(this._icono) || 
            !this._isSet(this._titulo) || !this._isSet(this._mensaje)) {
            return false;
        }

        // Configurar icono
        const icono = this._notificacion.querySelector(".toast-header i");
        if (icono) {
            this._icono.split(" ").forEach((clase) => {
                if (clase.trim()) {
                    icono.classList.add(clase);
                }
            });
        }

        // Configurar tipo (background)
        const header = this._notificacion.querySelector(".toast-header");
        if (header) {
            header.classList.add(this._tipo);
        }

        // Configurar título
        const titulo = this._notificacion.querySelector(".toast-header .me-auto");
        if (titulo) {
            titulo.innerHTML = `&nbsp;&nbsp;${this._titulo}`;
        }

        // Configurar mensaje
        const mensaje = this._notificacion.querySelector(".toast-body");
        if (mensaje) {
            mensaje.innerHTML = this._mensaje;
        }

        return true;
    },

    _capitalize: function(string) {
        if (!string || typeof string !== 'string') {
            return '';
        }
        return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
    },

    // Método para limpiar el estado
    reset: function() {
        this._notificacion = null;
        this._mensaje = null;
        this._titulo = null;
        this._icono = null;
        this._tipo = null;
        return this;
    },

    // Método para configurar toast ID por defecto
    setDefaultToastId: function(id) {
        if (typeof id === 'string' && id.trim()) {
            this._idtoast = id;
        }
        return this;
    }
};
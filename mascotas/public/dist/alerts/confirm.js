/**
 * Sistema de modales de confirmación y alerta
 * Compatible con Bootstrap 5
 */
window.confirmar = (
  () => {
    'use strict';

    // Configuración y constantes
    const CONFIG = {
      DEFAULT_SELECTOR: '#confirmar',
      CLEANUP_DELAY: 500,
      
      ICONS: {
        danger: 'bx bx-error-alt',
        warning: 'bx bx-error',
        success: 'bx bx-check-square',
        information: 'bx bx-info-square',
      },

      COLORS: {
        success: '#d1e7dd',
        warning: '#fff3cd',
        danger: '#f1aeb5',
        information: '#cff4fc',
      },

      DEFAULT_TITLES: {
        success: 'Correcto',
        warning: 'Atención',
        danger: 'Error',
        information: 'Atención',
      },

      BUTTON_CLASSES: {
        success: 'btn-success',
        warning: 'btn-warning',
        danger: 'btn-secondary',
        information: 'btn-info',
      },

      SELECTORS: {
        type: '[data-confirm-type]',
        message: '[data-confirm-message]',
        title: '[data-confirm-title]',
        icon: '[data-confirm-icon]',
        accept: '[data-confirm-accept]',
        cancel: '[data-confirm-cancel]',
        alertButtons: '[data-confirm-botones-estilo-alerta]',
        confirmButtons: '[data-confirm-botones-estilo-confirmar]',
      }
    };

    // Estado interno del modal
    let state = {
      element: null,
      message: null,
      title: null,
      icon: null,
      type: null,
      buttonType: null,
      isAlert: false,
    };

    /**
     * Resetea el estado interno
     */
    function resetState() {
      state = {
        element: null,
        message: null,
        title: null,
        icon: null,
        type: null,
        buttonType: null,
        isAlert: false,
      };
    }

    /**
     * Obtiene el elemento del modal
     */
    function getModalElement(selector = CONFIG.DEFAULT_SELECTOR) {
      if (typeof selector === 'string') {
        return document.querySelector(selector);
      }
      return document.querySelector(`#${selector.getAttribute('id')}`);
    }

    /**
     * Valida que todos los valores requeridos estén presentes
     */
    function validateRequiredValues() {
      const required = ['message', 'title', 'type', 'buttonType'];
      return required.every(key => state[key] !== null);
    }

    /**
     * Configuradores del modal
     */
    const setters = {
      type() {
        if (!state.type) return false;
        const typeElement = state.element.querySelector(CONFIG.SELECTORS.type);
        if (typeElement) {
          typeElement.style.backgroundColor = state.type;
        }
        return true;
      },

      message() {
        if (!state.message) return false;
        const messageElement = state.element.querySelector(CONFIG.SELECTORS.message);
        if (messageElement) {
          messageElement.innerText = state.message;
        }
        return true;
      },

      title() {
        if (!state.title) return false;
        const titleElement = state.element.querySelector(CONFIG.SELECTORS.title);
        if (titleElement) {
          titleElement.innerText = state.title;
        }
        return true;
      },

      icon() {
        if (!state.icon) return false;
        const iconElement = state.element.querySelector(CONFIG.SELECTORS.icon);
        if (iconElement) {
          iconElement.className = state.icon;
        }
        return true;
      },

      buttonType() {
        if (!state.buttonType) return false;
        const acceptButton = state.element.querySelector(CONFIG.SELECTORS.accept);
        if (acceptButton) {
          acceptButton.className = `btn ${state.buttonType} w-100`;
        }
        return true;
      },

      buttonVisibility() {
        const alertButtons = state.element.querySelector(CONFIG.SELECTORS.alertButtons);
        const confirmButtons = state.element.querySelector(CONFIG.SELECTORS.confirmButtons);
        
        if (alertButtons && confirmButtons) {
          if (state.isAlert) {
            alertButtons.classList.remove('d-none');
            confirmButtons.classList.add('d-none');
          } else {
            alertButtons.classList.add('d-none');
            confirmButtons.classList.remove('d-none');
          }
        }
        return true;
      }
    };

    /**
     * Aplica todas las configuraciones al modal
     */
    function applyModalConfiguration() {
      const results = [
        setters.type(),
        setters.message(),
        setters.title(),
        setters.icon(),
        setters.buttonType(),
        setters.buttonVisibility()
      ];

      return results.every(result => result === true);
    }

    /**
     * Configura el estado para un tipo específico de modal
     */
    function configureModalType(type, message, title, isAlert = false) {
      state.isAlert = isAlert;
      state.message = message;
      state.title = title || CONFIG.DEFAULT_TITLES[type];
      state.icon = CONFIG.ICONS[type];
      state.type = CONFIG.COLORS[type];
      state.buttonType = CONFIG.BUTTON_CLASSES[type];
    }

    /**
     * Configura los event listeners para los botones
     */
    function setupEventListeners(resolve) {
      const acceptBtn = state.element.querySelector(CONFIG.SELECTORS.accept);
      const cancelBtns = state.element.querySelectorAll(CONFIG.SELECTORS.cancel);

      // Enfocar el botón apropiado
      if (state.isAlert) {
        const alertCancelBtn = state.element.querySelector(
          `${CONFIG.SELECTORS.alertButtons} ${CONFIG.SELECTORS.cancel}`
        );
        if (alertCancelBtn) alertCancelBtn.focus();
      } else {
        if (acceptBtn) acceptBtn.focus();
      }

      const acceptHandler = () => {
        hideModal();
        resolve(true);
      };

      const cancelHandler = () => {
        hideModal();
        resolve(false);
      };

      // Agregar event listeners
      if (acceptBtn) {
        acceptBtn.addEventListener('click', acceptHandler);
      }

      cancelBtns.forEach(btn => {
        btn.addEventListener('click', cancelHandler);
      });

      // Cleanup function
      const removeHandlers = () => {
        setTimeout(() => {
          if (acceptBtn) {
            acceptBtn.removeEventListener('click', acceptHandler);
          }
          cancelBtns.forEach(btn => {
            btn.removeEventListener('click', cancelHandler);
          });
          state.element.removeEventListener('hide.bs.modal', removeHandlers);
        }, CONFIG.CLEANUP_DELAY);
      };

      state.element.addEventListener('hide.bs.modal', removeHandlers);
    }

    /**
     * Muestra el modal
     */
    function showModal(selector = null, ...parameters) {
      const elementSelector = selector || CONFIG.DEFAULT_SELECTOR;
      state.element = state.element || getModalElement(elementSelector);

      if (!state.element) {
        throw new Error('No se encontró el elemento del modal');
      }

      // Procesar parámetros si se proporcionan
      if (parameters.length > 0) {
        const { mensaje, titulo, tipo, icono } = parameters[0];
        
        if (tipo && typeof confirmar[tipo] === 'function') {
          return confirmar[tipo](mensaje, titulo);
        }
        
        if (icono && icono.toUpperCase() in CONFIG.ICONS) {
          state.icon = icono;
        }
      }

      // Validar y aplicar configuración
      if (!validateRequiredValues()) {
        throw new Error('Una o más de las propiedades requeridas para mostrar el mensaje de alerta poseen valor \'nulo\'');
      }

      if (!applyModalConfiguration()) {
        throw new Error('Error al configurar el modal');
      }

      // Configurar el evento de cierre
      const hideHandler = () => {
        state.element.removeEventListener('hide.bs.modal', hideHandler);
        resetState();
      };

      state.element.addEventListener('hide.bs.modal', hideHandler);

      // Mostrar el modal
      const bootstrapModal = new bootstrap.Modal(state.element);
      bootstrapModal.show();

      return waitForConfirmation();
    }

    /**
     * Oculta el modal y resetea el estado
     */
    function hideModal() {
      resetState();
    }

    /**
     * Retorna una promesa que se resuelve cuando el usuario interactúa con el modal
     */
    function waitForConfirmation() {
      return new Promise((resolve) => {
        setupEventListeners(resolve);
      });
    }

    // API pública
    return {
      // Propiedades públicas para compatibilidad
      _idConfirm: CONFIG.DEFAULT_SELECTOR,
      _mensaje: null,
      _titulo: null,
      _icono: null,
      _tipo: null,
      _tipoBoton: null,
      _es_alerta: false,

      // Configuraciones públicas para compatibilidad
      iconos: CONFIG.ICONS,
      tipos: CONFIG.COLORS,
      titulosDefault: CONFIG.DEFAULT_TITLES,

      // Métodos privados expuestos para compatibilidad
      _setTipo: setters.type,
      _setMensaje: setters.message,
      _setTitulo: setters.title,
      _setIcono: setters.icon,
      _setTipoBoton: setters.buttonType,
      _setAtributosEnNotificacion: applyModalConfiguration,
      _addPromiseHandlers: setupEventListeners,

      // Métodos principales
      show: showModal,
      hide: hideModal,
      waitForConfirmation,

      // Métodos de tipo específico
      Success(mensaje, titulo = null, alerta = false) {
        configureModalType('success', mensaje, titulo, alerta);
        return showModal(state.element);
      },

      Danger(mensaje, titulo = null, alerta = false) {
        configureModalType('danger', mensaje, titulo, alerta);
        return showModal(state.element);
      },

      Warning(mensaje, titulo = null, alerta = false) {
        configureModalType('warning', mensaje, titulo, alerta);
        return showModal(state.element);
      },

      Info(mensaje, titulo = null, alerta = false) {
        configureModalType('information', mensaje, titulo, alerta);
        return showModal(state.element);
      }
    };
  }
)();
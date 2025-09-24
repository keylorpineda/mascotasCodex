(function (global) {
  const EMAIL_REGEX = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
  const CEDULA_MAX_DIGITS = 11;

  function formatCedula(value) {
    const digits = (value || '').replace(/\D/g, '').slice(0, CEDULA_MAX_DIGITS);
    const parts = [];

    if (digits.length <= 3) {
      parts.push(digits);
    } else if (digits.length <= 10) {
      parts.push(digits.slice(0, 3));
      parts.push(digits.slice(3, 10));
    } else {
      parts.push(digits.slice(0, 3));
      parts.push(digits.slice(3, 10));
      parts.push(digits.slice(10));
    }

    if (parts.length === 0) {
      return '';
    }

    if (parts.length === 1) {
      return parts[0];
    }

    if (parts.length === 2) {
      return `${parts[0]}-${parts[1]}`;
    }

    return `${parts[0]}-${parts[1]}-${parts[2]}`;
  }

  function isValidEmail(value) {
    return EMAIL_REGEX.test(value);
  }

  function attachCedulaMask(input) {
    if (!input || input.dataset.maskCedulaBound) {
      return;
    }

    input.dataset.maskCedulaBound = '1';

    input.addEventListener('input', function () {
      const start = this.selectionStart;
      const formatted = formatCedula(this.value);
      this.value = formatted;
      if (typeof start === 'number') {
        this.setSelectionRange(this.value.length, this.value.length);
      }
    });

    input.addEventListener('blur', function () {
      this.value = formatCedula(this.value);
    });
  }

  function attachEmailMask(input) {
    if (!input || input.dataset.maskEmailBound) {
      return;
    }

    input.dataset.maskEmailBound = '1';

    input.addEventListener('input', function () {
      const sanitized = (this.value || '').replace(/\s+/g, '');
      if (sanitized !== this.value) {
        this.value = sanitized;
      }
      if (this.classList.contains('is-invalid') && (this.value === '' || isValidEmail(this.value))) {
        this.classList.remove('is-invalid');
        this.setCustomValidity('');
      }
    });

    input.addEventListener('blur', function () {
      const valor = (this.value || '').trim();
      if (valor === '' || isValidEmail(valor)) {
        this.classList.remove('is-invalid');
        this.setCustomValidity('');
        this.value = valor;
      } else {
        this.classList.add('is-invalid');
        this.setCustomValidity('Ingrese un correo válido');
      }
    });
  }

  function applyMasks(root = document) {
    const scope = root instanceof Element ? root : document;
    scope.querySelectorAll('[data-mask-cedula]').forEach(attachCedulaMask);
    scope.querySelectorAll('[data-mask-email]').forEach(attachEmailMask);
  }

  const FormMasks = {
    apply: applyMasks,
    formatCedula,
    isValidEmail
  };

  global.FormMasks = FormMasks;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => applyMasks());
  } else {
    applyMasks();
  }
})(window);

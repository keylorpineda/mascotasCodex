(function () {
  const formulario = $('#FORM_MASCOTA');
  const modal = $('#mascotaModal');
  const cedulaInput = formulario.find('[name="ID_PERSONA"]');
  const duennoFields = formulario.find('[data-duenno-field]');
  const duennoInputs = duennoFields.find('input');
  const estadoHiddenInput = formulario.find('[data-app-estado-hidden]');
  const estadoSelectContainer = formulario.find('[data-app-estado-select-container]');
  const estadoSelect = formulario.find('[data-app-estado-select]');
  const fotoArchivoInput = formulario.find('[name="FOTO_ARCHIVO"]');
  const fotoActualInput = formulario.find('[name="FOTO_ACTUAL"]');
  const fotoPreviewWrapper = formulario.find('[data-foto-preview]');
  const fotoPreviewImg = fotoPreviewWrapper.find('img');
  const fotoPreviewModalEl = document.getElementById('mascotaFotoPreviewModal');
  const fotoPreviewModalImg = fotoPreviewModalEl ? fotoPreviewModalEl.querySelector('[data-modal-foto]') : null;
  const fotoPreviewModal = typeof bootstrap !== 'undefined' && bootstrap.Modal && fotoPreviewModalEl
    ? bootstrap.Modal.getOrCreateInstance(fotoPreviewModalEl)
    : null;
  const defaultFotoUrl = typeof URL_IMAGEN_DEFAULT !== 'undefined' ? URL_IMAGEN_DEFAULT : '';
  let buscarPersonaTimeout = null;
  let buscarPersonaXHR = null;

  if (fotoPreviewModalEl && fotoPreviewModalImg && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    fotoPreviewModalEl.addEventListener('hidden.bs.modal', () => {
      fotoPreviewModalImg.setAttribute('src', '');
    });
  }

  function sanitizeCedula(value) {
    return (value || '').replace(/\D/g, '');
  }

  function resolverUrlImagen(path) {
    const candidato = (path || '').toString().trim();
    if (!candidato) {
      return defaultFotoUrl;
    }
    if (typeof isValidHttpUrl === 'function' && isValidHttpUrl(candidato)) {
      return candidato;
    }
    const normalizado = candidato.replace(/^\/+/, '');
    if (typeof base_url === 'function') {
      return base_url(normalizado);
    }
    return normalizado;
  }

  function toggleDuennoFields(editable) {
    const isEditable = !!editable;
    duennoFields.toggleClass('opacity-50', !isEditable);
    duennoInputs.prop('required', isEditable);
    duennoInputs.prop('readonly', !isEditable);
    duennoInputs.prop('disabled', false);
  }

  function fillDuennoFields(values) {
    const data = values || {};
    duennoInputs.each(function () {
      const $input = $(this);
      const name = $input.attr('name');
      if (Object.prototype.hasOwnProperty.call(data, name)) {
        $input.val(data[name]);
      } else {
        $input.val('');
      }
    });
  }

  function handlePersonaNotFound() {
    fillDuennoFields({
      NOMBRE_DUENNO: '',
      TELEFONO_DUENNO: '',
      CORREO_DUENNO: ''
    });
    toggleDuennoFields(true);
  }

  function handlePersonaFound(persona) {
    fillDuennoFields({
      NOMBRE_DUENNO: persona.NOMBRE || '',
      TELEFONO_DUENNO: persona.TELEFONO || '',
      CORREO_DUENNO: persona.CORREO || ''
    });
    const cedulaLimpia = sanitizeCedula(persona.ID_PERSONA || '');
    if (cedulaLimpia) {
      cedulaInput.val(cedulaLimpia);
    }
    toggleDuennoFields(false);
  }

  function consultarPersonaPorCedula(cedula) {
    const valor = sanitizeCedula(cedula);

    if (buscarPersonaTimeout) {
      clearTimeout(buscarPersonaTimeout);
      buscarPersonaTimeout = null;
    }

    if (buscarPersonaXHR) {
      buscarPersonaXHR.abort();
      buscarPersonaXHR = null;
    }

    if (valor === '') {
      fillDuennoFields({
        NOMBRE_DUENNO: '',
        TELEFONO_DUENNO: '',
        CORREO_DUENNO: ''
      });
      toggleDuennoFields(false);
      return;
    }

    buscarPersonaTimeout = setTimeout(() => {
      buscarPersonaXHR = $.ajax({
        url: URL_PERSONAS.buscar,
        data: { cedula: valor },
        dataType: 'json',
        method: 'GET'
      })
        .done(resp => {
          const data = resp && resp.data ? resp.data : resp;
          if (data && data.ID_PERSONA) {
            handlePersonaFound(data);
          } else {
            handlePersonaNotFound();
          }
        })
        .fail((_, textStatus) => {
          if (textStatus !== 'abort') {
            handlePersonaNotFound();
          }
        })
        .always(() => {
          buscarPersonaXHR = null;
        });
    }, 250);
  }
  function columnas() {
    return [
      { title: 'ID', data: 'ID_MASCOTA' },
      { title: 'Mascota', data: 'NOMBRE_MASCOTA' },
      { title: 'Dueño', data: 'DUENNO' },
      {
        title: 'Foto',
        data: 'FOTO_URL',
        orderable: false,
        render: (d, __, row) => {
          const url = resolverUrlImagen(d);
          const titulo = row.NOMBRE_MASCOTA ? `Fotografía de ${row.NOMBRE_MASCOTA}` : 'Fotografía de mascota';
          return `
            <button type="button" class="btn btn-link p-0 border-0" data-foto-preview="${url}" title="${titulo} (clic para ampliar)">
              <img src="${url}" alt="${titulo}" class="rounded" style="width:42px;height:42px;object-fit:cover;">
            </button>
          `;
        }
      },
      { title: 'Estado', data: 'ESTADO', render: d => d === 'ACT' ? 'ACTIVO' : 'INACTIVO' },
      {
        title: 'Acciones', data: null, orderable: false, searchable: false, className: 'text-center', render: (_, __, row) => `
        <div class="d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" data-editar data-id="${row.ID_MASCOTA}">
            <i class='bx bx-edit-alt'></i>
          </button>
          <button type="button" class="btn btn-outline-danger btn-sm rounded-pill" data-eliminar data-id="${row.ID_MASCOTA}">
            <i class='bx bx-block'></i>
          </button>
        </div>
      ` }
    ];
  }

  function bloquearEstado(esAlta) {
    if (esAlta) {
      estadoSelectContainer.addClass('d-none');
      estadoSelect.prop('disabled', true);
      estadoHiddenInput.prop('disabled', false).val('ACT');
    } else {
      estadoSelectContainer.removeClass('d-none');
      const valor = estadoSelect.val() || estadoHiddenInput.val() || 'ACT';
      estadoSelect.val(valor).prop('disabled', false);
      estadoHiddenInput.prop('disabled', true);
    }
  }

  $(document).on('click', '[data-bs-target="#mascotaModal"]', function () {
    formulario[0].reset();
    formulario.removeData('editar');
    formulario.find('[name="ID_MASCOTA"]').val('');
    fotoActualInput.val('');
    bloquearEstado(true);
    modal.find('.modal-title').text('Registrar Mascota');
    toggleDuennoFields(false);
    fillDuennoFields({
      NOMBRE_DUENNO: '',
      TELEFONO_DUENNO: '',
      CORREO_DUENNO: ''
    });
    fotoPreviewImg.attr('src', '');
    fotoPreviewWrapper.addClass('d-none');
  });

  function guardarMascota(ev) {
    ev.preventDefault();
    const $btn = formulario.find('button[type="submit"]').prop('disabled', true);
    const url = formulario.data('editar') ? URL_MASCOTAS.editar : URL_MASCOTAS.guardar;

    const formData = new FormData(formulario[0]);
    const cedulaSanitizada = sanitizeCedula(cedulaInput.val());
    formData.set('ID_PERSONA', cedulaSanitizada);
    formData.set('FOTO_ACTUAL', fotoActualInput.val() || '');

    $.ajax({
      url,
      method: 'POST',
      data: formData,
      dataType: 'json',
      processData: false,
      contentType: false
    })
      .done(resp => {
        const tipo = (resp && (resp.type || resp.TIPO) || '').toString().toUpperCase();
        const mensaje = resp && (resp.message || resp.MENSAJE) ? (resp.message || resp.MENSAJE) : 'Respuesta recibida.';
        if (tipo && alerta[capitalize(tipo)]) {
          alerta[capitalize(tipo)](mensaje).show();
        } else {
          alerta.Info(mensaje).show();
        }
        if (tipo === 'SUCCESS') {
          modal.modal('hide');
          tabla.ajax.reload(null, false);
        }
      })
      .fail(() => alerta.Danger('No se pudo procesar la solicitud').show())
      .always(() => $btn.prop('disabled', false));
  }

  function editarMascota() {
    const id = $(this).data('id');
    $.getJSON(URL_MASCOTAS.obtener, { idmascota: id }, resp => {
      const data = resp && resp.data ? resp.data : resp;
      formulario[0].reset();
      Object.entries(data || {}).forEach(([k, v]) => { formulario.find(`[name="${k}"]`).val(v); });
      formulario.data('editar', true);
      bloquearEstado(false);
      modal.find('.modal-title').text('Editar Mascota');
      modal.modal('show');
      toggleDuennoFields(false);
      fillDuennoFields({
        NOMBRE_DUENNO: '',
        TELEFONO_DUENNO: '',
        CORREO_DUENNO: ''
      });
      const cedulaActual = formulario.find('[name="ID_PERSONA"]').val();
      formulario.find('[name="ID_PERSONA"]').val(sanitizeCedula(cedulaActual));
      consultarPersonaPorCedula(cedulaActual);
      const foto = data && data.FOTO_URL ? data.FOTO_URL : '';
      fotoActualInput.val(foto || '');
      actualizarPreview(fotoActualInput.val());
    });
  }

  function eliminarMascota() {
    const id = $(this).data('id');
    confirmar.Warning('¿Desea inactivar esta mascota?', 'Confirmación requerida').then(resp => {
      if (!resp) return;
      $.post(URL_MASCOTAS.eliminar, { idmascota: id }, r => {
        const tipo = (r && (r.type || r.TIPO) || '').toString().toUpperCase();
        const mensaje = r && (r.message || r.MENSAJE) ? (r.message || r.MENSAJE) : 'Respuesta recibida.';
        if (tipo && alerta[capitalize(tipo)]) {
          alerta[capitalize(tipo)](mensaje).show();
          if (tipo === 'SUCCESS') tabla.ajax.reload(null, false);
        } else {
          alerta.Warning('Respuesta inválida del servidor').show();
        }
      }, 'json').fail(() => alerta.Danger('No se pudo inactivar').show());
    });
  }

  const tabla = $('#tmascotas').DataTable({
    ajax: {
      url: URL_MASCOTAS.obtener,
      dataSrc: function (resp) {
        if (!resp) return [];
        if (Array.isArray(resp.data)) return resp.data;
        if (resp.data && Array.isArray(resp.data.data)) return resp.data.data;
        return resp.data && typeof resp.data === 'object' ? Object.values(resp.data) : [];
      },
      error: function (xhr) {
        let mensaje = 'Ocurrió un error al obtener el listado de mascotas.';

        if (xhr.responseJSON && xhr.responseJSON.error) {
          mensaje = xhr.responseJSON.error;
        } else if (xhr.responseText) {
          try {
            const data = JSON.parse(xhr.responseText);
            if (data && data.error) {
              mensaje = data.error;
            }
          } catch (error) {
            console.error('No se pudo parsear la respuesta de error:', error);
          }
        }

        if (typeof alerta !== 'undefined' && alerta.Danger) {
          alerta.Danger(mensaje).show();
        }

        console.error(xhr.responseText || mensaje);
      }
    },
    columns: columnas(),
    language: {
      lengthMenu: "_MENU_ por página",
      zeroRecords: "No hay registros",
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 a 0 de 0 registros",
      infoFiltered: "(filtrado de _MAX_ en total)",
      paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" },
      loadingRecords: "Cargando...",
      processing: "Procesando...",
      search: "Buscar:"
    },
    dom: "<'row'<'col-sm-6'l><'col-sm-6 text-end'f>>rt<'row'<'col-sm-6'i><'col-sm-6'p>>"
  });

  formulario.on('submit', guardarMascota);
  $('#tmascotas').on('click', '[data-editar]', editarMascota);
  $('#tmascotas').on('click', '[data-eliminar]', eliminarMascota);
  function actualizarPreview(url) {
    const tieneReferencia = !!(url && url.toString().trim() !== '');
    const resolved = tieneReferencia ? resolverUrlImagen(url) : '';
    if (tieneReferencia && resolved) {
      fotoPreviewImg.attr('src', resolved);
      fotoPreviewWrapper.removeClass('d-none');
    } else {
      fotoPreviewImg.attr('src', '');
      fotoPreviewWrapper.addClass('d-none');
    }
  }

  fotoArchivoInput.on('change', function () {
    const file = this.files && this.files[0] ? this.files[0] : null;
    if (!file) {
      actualizarPreview(fotoActualInput.val());
      return;
    }
    const reader = new FileReader();
    reader.onload = function (ev) {
      fotoPreviewImg.attr('src', ev.target.result);
      fotoPreviewWrapper.removeClass('d-none');
    };
    reader.readAsDataURL(file);
  });

  cedulaInput.on('input blur', function () {
    consultarPersonaPorCedula($(this).val());
  });

  $(document).on('click', '[data-foto-preview]', function () {
    const url = $(this).data('fotoPreview');
    if (!url) return;
    if (fotoPreviewModal && fotoPreviewModalImg) {
      fotoPreviewModalImg.setAttribute('src', url);
      fotoPreviewModal.show();
    } else {
      window.open(url, '_blank');
    }
  });
})();

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
  const fotoPreviewWrapper = formulario.find('[data-foto-preview]');
  const fotoPreviewImg = fotoPreviewWrapper.find('img');
  let buscarPersonaTimeout = null;
  let buscarPersonaXHR = null;

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
    toggleDuennoFields(false);
  }

  function consultarPersonaPorCedula(cedula) {
    const valor = (cedula || '').trim();

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
        render: d => {
          if (!d) return '';
          const url = (function resolveUrl(path) {
            if (!path) return null;
            if (typeof isValidHttpUrl === 'function' && isValidHttpUrl(path)) {
              return path;
            }
            const normalized = path.replace(/^\/+/, '');
            return typeof base_url === 'function' ? base_url(normalized) : normalized;
          })(d);
          if (!url) return '';
          return `<img src="${url}" class="img-thumbnail" style="width:40px;height:40px;object-fit:cover;">`;
        }
      },
      { title: 'Estado', data: 'ESTADO', render: d => d === 'ACT' ? 'ACTIVO' : 'INACTIVO' },
      {
        title: 'Acciones', data: null, orderable: false, searchable: false, render: (_, __, row) => `
        <button type="button" class="btn btn-primary btn-sm" data-editar data-id="${row.ID_MASCOTA}">
          <i class='bx bx-edit-alt'></i>
        </button>
        <button type="button" class="btn btn-danger btn-sm" data-eliminar data-id="${row.ID_MASCOTA}">
          <i class='bx bx-trash'></i>
        </button>
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
      consultarPersonaPorCedula(formulario.find('[name="ID_PERSONA"]').val());
      actualizarPreview(formulario.find('[name="FOTO_URL"]').val());
    });
  }

  function eliminarMascota() {
    const id = $(this).data('id');
    confirmar.Warning('¿Desea eliminar el registro?', 'Atención').then(resp => {
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
      }, 'json').fail(() => alerta.Danger('No se pudo eliminar').show());
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
      data: function (d) {
        const $f = $('[data-app-filtros]');
        d.nombre = $f.find('[data-app-filtro-nombre]').val() || '';
        d.idpersona = $f.find('[data-app-filtro-cedula]').val() || '';
        d.estado = $f.find('[data-app-filtro-estado]').val() || '';
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

  $('[data-app-filtro-buscar]').on('click', function () { tabla.ajax.reload(); });
  formulario.on('submit', guardarMascota);
  $('#tmascotas').on('click', '[data-editar]', editarMascota);
  $('#tmascotas').on('click', '[data-eliminar]', eliminarMascota);
  function actualizarPreview(url) {
    const resolved = (function () {
      if (!url) return null;
      if (typeof isValidHttpUrl === 'function' && isValidHttpUrl(url)) return url;
      const normalized = url.replace(/^\/+/, '');
      return typeof base_url === 'function' ? base_url(normalized) : normalized;
    })();
    if (resolved) {
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
      actualizarPreview(formulario.find('[name="FOTO_URL"]').val());
      return;
    }
    const reader = new FileReader();
    reader.onload = function (ev) {
      fotoPreviewImg.attr('src', ev.target.result);
      fotoPreviewWrapper.removeClass('d-none');
    };
    reader.readAsDataURL(file);
  });

  formulario.find('[name="FOTO_URL"]').on('input blur', function () {
    if (!fotoArchivoInput[0].files.length) {
      actualizarPreview($(this).val());
    }
  });

  cedulaInput.on('input blur', function () {
    consultarPersonaPorCedula($(this).val());
  });
})();

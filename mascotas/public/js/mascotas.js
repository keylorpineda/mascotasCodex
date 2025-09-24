(function () {
  const formulario = $('#FORM_MASCOTA');
  const modal = $('#mascotaModal');
  const cedulaInput = formulario.find('[name="ID_PERSONA"]');
  const duennoInputs = formulario.find('[data-duenno-field] input');
  const estadoHiddenInput = formulario.find('[data-app-estado-hidden]');
  const estadoSelectContainer = formulario.find('[data-app-estado-select-container]');
  const estadoSelect = formulario.find('[data-app-estado-select]');
  const fotoUrlInput = formulario.find('[data-app-foto-url]');
  const fotoArchivoInput = formulario.find('[data-app-foto-archivo]');
  const fotoPreview = formulario.find('[data-app-foto-preview]');
  const fotoActualInput = formulario.find('[data-app-foto-actual]');
  let buscarPersonaTimeout = null;
  let buscarPersonaXHR = null;
  let fotoObjectUrl = null;

  function revokeFotoObjectUrl() {
    if (fotoObjectUrl) {
      URL.revokeObjectURL(fotoObjectUrl);
      fotoObjectUrl = null;
    }
  }

  function setCamposDuenoRequeridos(sonRequeridos) {
    duennoInputs.each(function () {
      $(this).prop('required', sonRequeridos);
    });
    formulario.data('persona-existe', !sonRequeridos);
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

  function getPreviewUrl(valor) {
    if (!valor) {
      return null;
    }

    if (typeof isValidHttpUrl === 'function' && isValidHttpUrl(valor)) {
      return valor;
    }

    if (/^https?:\/\//i.test(valor)) {
      return valor;
    }

    return base_url(String(valor).replace(/^\/+/, ''));
  }

  function setPreview(url) {
    if (url) {
      fotoPreview.attr('src', url).removeClass('d-none');
    } else {
      fotoPreview.attr('src', '').addClass('d-none');
    }
  }

  function resetFoto() {
    revokeFotoObjectUrl();
    fotoUrlInput.val('');
    fotoArchivoInput.val('');
    fotoActualInput.val('');
    setPreview(null);
  }

  function handlePersonaNotFound() {
    fillDuennoFields({
      NOMBRE_DUENNO: '',
      TELEFONO_DUENNO: '',
      CORREO_DUENNO: ''
    });
    setCamposDuenoRequeridos(true);
  }

  function handlePersonaFound(persona) {
    fillDuennoFields({
      NOMBRE_DUENNO: persona.NOMBRE || '',
      TELEFONO_DUENNO: persona.TELEFONO || '',
      CORREO_DUENNO: persona.CORREO || ''
    });
    setCamposDuenoRequeridos(false);
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
      handlePersonaNotFound();
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
          if (resp && resp.ID_PERSONA) {
            handlePersonaFound(resp);
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
          const url = getPreviewUrl(d);
          if (!url) {
            return '';
          }
          return `<img src="${url}" class="img-thumbnail" style="width:40px;height:40px;">`;
        }
      },
      { title: 'Estado', data: 'ESTADO', render: d => (d === 'ACT' ? 'ACTIVO' : 'INACTIVO') },
      {
        title: 'Acciones',
        data: null,
        orderable: false,
        searchable: false,
        render: (_, __, row) => `
        <button type="button" class="btn btn-primary btn-sm" data-editar data-id="${row.ID_MASCOTA}">
          <i class='bx bx-edit-alt'></i>
        </button>
        <button type="button" class="btn btn-danger btn-sm" data-eliminar data-id="${row.ID_MASCOTA}">
          <i class='bx bx-trash'></i>
        </button>
      `
      }
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

  function prepararFormularioAlta() {
    formulario[0].reset();
    formulario.removeData('editar');
    formulario.find('[name="ID_MASCOTA"]').val('');
    formulario.removeData('persona-existe');
    bloquearEstado(true);
    modal.find('.modal-title').text('Registrar Mascota');
    resetFoto();
    setCamposDuenoRequeridos(true);
    fillDuennoFields({
      NOMBRE_DUENNO: '',
      TELEFONO_DUENNO: '',
      CORREO_DUENNO: ''
    });
    if (typeof FormMasks !== 'undefined') {
      FormMasks.apply(formulario[0]);
    }
  }

  $(document).on('click', '[data-bs-target="#mascotaModal"]', prepararFormularioAlta);

  function actualizarPreviewDesdeUrl() {
    const valor = (fotoUrlInput.val() || '').trim();
    if (valor === '') {
      if (!fotoArchivoInput.length || !fotoArchivoInput[0].files.length) {
        setPreview(fotoActualInput.val() ? getPreviewUrl(fotoActualInput.val()) : null);
      }
      return;
    }
    revokeFotoObjectUrl();
    setPreview(getPreviewUrl(valor));
  }

  fotoUrlInput.on('input', function () {
    const valor = (this.value || '').trim();
    if (valor !== '') {
      fotoArchivoInput.val('');
    }
    actualizarPreviewDesdeUrl();
  });

  fotoUrlInput.on('blur', actualizarPreviewDesdeUrl);

  fotoArchivoInput.on('change', function () {
    revokeFotoObjectUrl();
    const file = this.files && this.files[0];
    if (file) {
      fotoUrlInput.val('');
      fotoObjectUrl = URL.createObjectURL(file);
      setPreview(fotoObjectUrl);
    } else if (fotoActualInput.val()) {
      setPreview(getPreviewUrl(fotoActualInput.val()));
    } else {
      setPreview(null);
    }
  });

  function guardarMascota(ev) {
    ev.preventDefault();

    if (!formulario[0].checkValidity()) {
      formulario[0].reportValidity();
      return;
    }

    const $btn = formulario.find('button[type="submit"]').prop('disabled', true);
    const url = formulario.data('editar') ? URL_MASCOTAS.editar : URL_MASCOTAS.guardar;
    const datos = new FormData(formulario[0]);

    $.ajax({
      url,
      method: 'POST',
      data: datos,
      dataType: 'json',
      processData: false,
      contentType: false
    })
      .done(resp => {
        if (resp && resp.TIPO) {
          alerta[capitalize(resp.TIPO)](resp.MENSAJE).show();
          if (resp.TIPO === 'SUCCESS') {
            modal.modal('hide');
            tabla.ajax.reload(null, false);
          }
        } else {
          alerta.Warning('Respuesta inválida del servidor').show();
        }
      })
      .fail(() => alerta.Danger('No se pudo procesar la solicitud').show())
      .always(() => $btn.prop('disabled', false));
  }

  function editarMascota() {
    const id = $(this).data('id');
    $.getJSON(URL_MASCOTAS.obtener, { idmascota: id }, data => {
      prepararFormularioAlta();
      Object.entries(data || {}).forEach(([k, v]) => {
        formulario.find(`[name="${k}"]`).val(v);
      });
      formulario.data('editar', true);
      bloquearEstado(false);
      fotoActualInput.val(data && data.FOTO_URL ? data.FOTO_URL : '');
      if (data && data.FOTO_URL) {
        setPreview(getPreviewUrl(data.FOTO_URL));
        fotoUrlInput.val(data.FOTO_URL);
      }
      modal.find('.modal-title').text('Editar Mascota');
      modal.modal('show');
      consultarPersonaPorCedula(formulario.find('[name="ID_PERSONA"]').val());
    });
  }

  function eliminarMascota() {
    const id = $(this).data('id');
    confirmar.Warning('¿Desea eliminar el registro?', 'Atención').then(resp => {
      if (!resp) return;
      $.post(URL_MASCOTAS.eliminar, { idmascota: id }, r => {
        if (r && r.TIPO) {
          alerta[capitalize(r.TIPO)](r.MENSAJE).show();
          if (r.TIPO === 'SUCCESS') tabla.ajax.reload(null, false);
        } else {
          alerta.Warning('Respuesta inválida del servidor').show();
        }
      }, 'json').fail(() => alerta.Danger('No se pudo eliminar').show());
    });
  }

  const tabla = $('#tmascotas').DataTable({
    ajax: {
      url: URL_MASCOTAS.obtener,
      dataSrc: 'data',
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

  $('[data-app-filtro-buscar]').on('click', function () {
    tabla.ajax.reload();
  });

  $('[data-app-filtro-estado]').on('change', function () {
    tabla.ajax.reload();
  });

  formulario.on('submit', guardarMascota);
  $('#tmascotas').on('click', '[data-editar]', editarMascota);
  $('#tmascotas').on('click', '[data-eliminar]', eliminarMascota);
  cedulaInput.on('input', function () {
    consultarPersonaPorCedula($(this).val());
  });
  cedulaInput.on('blur', function () {
    consultarPersonaPorCedula($(this).val());
  });
})();

(function () {
  const formulario = $('#FORM_MASCOTA');
  const modal = $('#mascotaModal');
  const cedulaInput = formulario.find('[name="ID_PERSONA"]');
  const duennoFields = formulario.find('[data-duenno-field]');
  const duennoInputs = duennoFields.find('input');
  let buscarPersonaTimeout = null;
  let buscarPersonaXHR = null;

  function toggleDuennoFields(show) {
    duennoFields.toggleClass('d-none', !show);
    duennoInputs.prop('required', show);
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
      { title: 'Foto', data: 'FOTO_URL', render: d => d ? `<img src="${d}" class="img-thumbnail" style="width:40px;height:40px;">` : '' },
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

  function bloquearEstado(b) {
    const $sel = formulario.find('[name="ESTADO"]');
    if (b) { $sel.val('ACT').prop('disabled', true); } else { $sel.prop('disabled', false); }
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
  });

  function guardarMascota(ev) {
    ev.preventDefault();
    const $btn = formulario.find('button[type="submit"]').prop('disabled', true);
    const url = formulario.data('editar') ? URL_MASCOTAS.editar : URL_MASCOTAS.guardar;

    $.ajax({ url, method: 'POST', data: formulario.serialize(), dataType: 'json' })
      .done(resp => {
        if (resp && resp.TIPO) {
          alerta[capitalize(resp.TIPO)](resp.MENSAJE).show();
          if (resp.TIPO === 'SUCCESS') { modal.modal('hide'); tabla.ajax.reload(null, false); }
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
        console.error(xhr.responseText);
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
   cedulaInput.on('input', function () {
    consultarPersonaPorCedula($(this).val());
  });
  cedulaInput.on('blur', function () {
    consultarPersonaPorCedula($(this).val());
  });
})();

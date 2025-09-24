(function () {
  const formularioCrear = $('#FORM_PERSONA_CREAR');
  const formularioEditar = $('#FORM_PERSONA_EDITAR');
  const modalCrearEl = document.getElementById('personaCrearModal');
  const modalEditarEl = document.getElementById('personaEditarModal');
  const hasBootstrap = typeof bootstrap !== 'undefined' && bootstrap.Modal;
  const modalCrear = hasBootstrap && modalCrearEl ? bootstrap.Modal.getOrCreateInstance(modalCrearEl) : null;
  const modalEditar = hasBootstrap && modalEditarEl ? bootstrap.Modal.getOrCreateInstance(modalEditarEl) : null;

  function obtenerTipo(resp) {
    return (resp && (resp.type || resp.TIPO) || '').toString().toUpperCase();
  }

  function obtenerMensaje(resp, fallback = 'Respuesta recibida.') {
    if (resp && (resp.message || resp.MENSAJE)) {
      return resp.message || resp.MENSAJE;
    }
    return fallback;
  }

  function mostrarAlerta(resp, fallback) {
    const tipo = obtenerTipo(resp);
    const mensaje = obtenerMensaje(resp, fallback);
    if (tipo && alerta[capitalize(tipo)]) {
      alerta[capitalize(tipo)](mensaje).show();
    } else {
      alerta.Info(mensaje).show();
    }
    return tipo;
  }

  function renderAcciones(_, __, row) {
    return `
      <button type="button" class="btn btn-primary btn-sm" data-editar data-id="${row.ID_PERSONA}" data-bs-toggle="modal" data-bs-target="#personaEditarModal">
        <i class='bx bx-edit-alt'></i>
      </button>
      <button type="button" class="btn btn-danger btn-sm" data-eliminar data-id="${row.ID_PERSONA}">
        <i class='bx bx-trash'></i>
      </button>
    `;
  }

  function guardarPersona(ev) {
    ev.preventDefault();
    if (!formularioCrear.length) return;
    const $btn = formularioCrear.find('button[type="submit"]').prop('disabled', true);

    $.ajax({
      url: base_url('personas/guardar'),
      method: 'POST',
      data: formularioCrear.serialize(),
      dataType: 'json'
    })
      .done(resp => {
        const tipo = mostrarAlerta(resp, 'No se recibió respuesta del servidor.');
        if (tipo === 'SUCCESS') {
          formularioCrear[0].reset();
          if (modalCrear) modalCrear.hide();
          tabla.ajax.reload(null, false);
        }
      })
      .fail(() => alerta.Danger('No se pudo procesar la solicitud').show())
      .always(() => $btn.prop('disabled', false));
  }

  function actualizarPersona(ev) {
    ev.preventDefault();
    if (!formularioEditar.length) return;
    const $btn = formularioEditar.find('button[type="submit"]').prop('disabled', true);

    $.ajax({
      url: base_url('personas/editar'),
      method: 'POST',
      data: formularioEditar.serialize(),
      dataType: 'json'
    })
      .done(resp => {
        const tipo = mostrarAlerta(resp, 'No se pudo actualizar la persona.');
        if (tipo === 'SUCCESS') {
          if (modalEditar) modalEditar.hide();
          tabla.ajax.reload(null, false);
        }
      })
      .fail(() => alerta.Danger('No se pudo procesar la solicitud').show())
      .always(() => $btn.prop('disabled', false));
  }

  function editarPersona(ev) {
    ev.preventDefault();
    ev.stopPropagation();
    if (!formularioEditar.length) return;

    const id = $(this).data('id');
    $.getJSON(base_url('personas/obtener'), { idpersona: id })
      .done(resp => {
        const data = resp && resp.data ? resp.data : resp;
        if (!data || typeof data !== 'object' || !data.ID_PERSONA) {
          alerta.Warning('No se encontraron datos de la persona seleccionada').show();
          return;
        }
        formularioEditar[0].reset();
        Object.entries(data).forEach(([k, v]) => {
          formularioEditar.find(`[name="${k}"]`).val(v);
        });
        formularioEditar.find('[name="ID"]').val(data.ID_PERSONA || id);
        if (modalEditar) modalEditar.show();
      })
      .fail(() => {
        alerta.Danger('No se pudo obtener la información de la persona').show();
      });
  }

  function eliminarPersona() {
    const id = $(this).data('id');
    confirmar.Warning('¿Desea eliminar el registro?', 'Atención').then(resp => {
      if (!resp) return;
      $.post(base_url('personas/eliminar'), { idpersona: id }, r => {
        const tipo = mostrarAlerta(r, 'No se pudo eliminar el registro.');
        if (tipo === 'SUCCESS') tabla.ajax.reload(null, false);
      }, 'json').fail(() => alerta.Danger('No se pudo eliminar').show());
    });
  }

  const tabla = $('#tpersonas').DataTable({
    ajax: {
      url: base_url('personas/obtener'),
      dataSrc: function (resp) {
        if (!resp) return [];
        if (Array.isArray(resp.data)) return resp.data;
        if (resp.data && Array.isArray(resp.data.data)) return resp.data.data;
        return resp.data && typeof resp.data === 'object' ? Object.values(resp.data) : [];
      },
      data: function (d) {
        const $f = $('[data-app-filtros]');
        d.nombre = $f.find('[data-app-filtro-nombre]').val() || '';
        d.telefono = $f.find('[data-app-filtro-telefono]').val() || '';
        d.correo = $f.find('[data-app-filtro-correo]').val() || '';
        d.estado = $f.find('[data-app-filtro-estado]').val() || '';
      }
    },
    columns: [
      { data: 'ID_PERSONA' },
      { data: 'NOMBRE' },
      { data: 'TELEFONO' },
      { data: 'CORREO' },
      { data: 'ESTADO', render: d => d === 'ACT' ? 'ACTIVO' : 'INACTIVO' },
      { data: null, render: renderAcciones, orderable: false, searchable: false }
    ],
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
    dom: "<'row'<'col-sm-6'l><'col-sm-6 text-end'f>>" + "rt" + "<'row'<'col-sm-6'i><'col-sm-6'p>>"
  });

  $('[data-app-filtro-buscar]').on('click', function () {
    tabla.ajax.reload();
  });

  if (formularioCrear.length) formularioCrear.on('submit', guardarPersona);
  if (formularioEditar.length) formularioEditar.on('submit', actualizarPersona);
  if (formularioEditar.length) $('#tpersonas').on('click', '[data-editar]', editarPersona);
  $('#tpersonas').on('click', '[data-eliminar]', eliminarPersona);
})();

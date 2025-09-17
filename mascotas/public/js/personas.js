(function () {
  const formulario = $('#FORM_PERSONA');

  function renderAcciones(_, __, row) {
    return `
      <button type="button" class="btn btn-primary btn-sm" data-editar data-id="${row.ID_PERSONA}">
        <i class='bx bx-edit-alt'></i>
      </button>
      <button type="button" class="btn btn-danger btn-sm" data-eliminar data-id="${row.ID_PERSONA}">
        <i class='bx bx-trash'></i>
      </button>
    `;
  }

  function guardarPersona(ev) {
    ev.preventDefault();
    const $btn = formulario.find('button[type="submit"]').prop('disabled', true);
    const url = formulario.data('editar')
      ? base_url('personas/editar')
      : base_url('personas/guardar');
    $.ajax({
      url,
      method: 'POST',
      data: formulario.serialize(),
      dataType: 'json'
    }).done(resp => {
      if (resp && resp.TIPO) {
        alerta[capitalize(resp.TIPO)](resp.MENSAJE).show();
        if (resp.TIPO === 'SUCCESS') {
          formulario[0].reset();
          formulario.removeData('editar');
          tabla.ajax.reload(null, false);
        }
      } else {
        alerta.Warning('Respuesta inválida del servidor').show();
      }
    }).fail(() => {
      alerta.Danger('No se pudo procesar la solicitud').show();
    }).always(() => $btn.prop('disabled', false));
  }

  function editarPersona() {
    const id = $(this).data('id');
    $.getJSON(base_url('personas/obtener'), { idpersona: id }, data => {
      Object.entries(data || {}).forEach(([k, v]) => {
        formulario.find(`[name="${k}"]`).val(v);
      });
      formulario.data('editar', true);
    });
  }

  function eliminarPersona() {
    const id = $(this).data('id');
    confirmar.Warning('¿Desea eliminar el registro?', 'Atención').then(resp => {
      if (!resp) return;
      $.post(base_url('personas/eliminar'), { idpersona: id }, r => {
        if (r && r.TIPO) {
          alerta[capitalize(r.TIPO)](r.MENSAJE).show();
          if (r.TIPO === 'SUCCESS') tabla.ajax.reload(null, false);
        } else {
          alerta.Warning('Respuesta inválida del servidor').show();
        }
      }, 'json').fail(() => alerta.Danger('No se pudo eliminar').show());
    });
  }

  const tabla = $('#tpersonas').DataTable({
    ajax: {
      url: base_url('personas/obtener'),
      dataSrc: 'data',
      data: function (d) {
        const $f = $('[data-app-filtros]');
        d.nombre = $f.find('[data-app-filtro-nombre]').val() || '';
        d.telefono = $f.find('[data-app-filtro-telefono]').val() || '';
        d.correo = $f.find('[data-app-filtro-correo]').val() || '';
      }
    },
    columns: [
      { data: 'ID_PERSONA' },
      { data: 'NOMBRE' },
      { data: 'TELEFONO' },
      { data: 'CORREO' },
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

  formulario.on('submit', guardarPersona);
  $('#tpersonas').on('click', '[data-editar]', editarPersona);
  $('#tpersonas').on('click', '[data-eliminar]', eliminarPersona);
})();

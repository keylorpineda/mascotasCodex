(function () {
  const formulario = $('#FORM_MASCOTA');
  const modal = $('#mascotaModal');

  function columnas() {
    return [
      { title: 'ID', data: 'ID_MASCOTA' },
      { title: 'Mascota', data: 'NOMBRE_MASCOTA' },
      { title: 'Dueño', data: 'DUENNO' },
      { title: 'Foto', data: 'FOTO_URL', render: d => d ? `<img src="${d}" class="img-thumbnail" style="width:40px;height:40px;">` : '' },
      { title: 'Estado', data: 'ESTADO', render: d => d === 'ACT' ? 'ACTIVO' : 'INACTIVO' },
      { title: 'Acciones', data: null, orderable: false, searchable: false, render: (_, __, row) => `
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

  $(document).on('click','[data-bs-target="#mascotaModal"]', function(){
    formulario[0].reset();
    formulario.removeData('editar');
    formulario.find('[name="ID_MASCOTA"]').val('');
    bloquearEstado(true);
    modal.find('.modal-title').text('Registrar Mascota');
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
        d.nombre    = $f.find('[data-app-filtro-nombre]').val() || '';
        d.idpersona = $f.find('[data-app-filtro-cedula]').val() || '';
        d.estado    = $f.find('[data-app-filtro-estado]').val() || '';
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
})();

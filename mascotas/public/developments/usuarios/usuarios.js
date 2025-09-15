(function () {
	const FORMULARIO_NUEVOS_USUARIOS = document.querySelector("#FORMULARIO-NUEVOS-USUARIOS"),
	  FORMULARIO_ACTUALIZAR_USUARIOS = document.querySelector("#FORMULARIO-ACTUALIZAR-USUARIOS"),
	  USUARIOS_FILTROS_CONTAINER = document.querySelector("[data-app-filtros]"),
	  BOTON_BUSCAR_USUARIOS = document.querySelector("[data-app-filtro-buscar]"),
	  ACTUALIZAR_USUARIO_MODAL = document.querySelector("#actualizarUsuariosModal"),
	  GENERAR_USUARIO_MODAL = document.querySelector("#nuevosUsuariosModal"),
	  TABLA_USUARIOS = document.querySelector("#tusuarios"),
	  PERMISO_GUARDAR = document.querySelector("#PERMISO_GUARDAR")?.value,
	  PERMISO_EDITAR = document.querySelector("#PERMISO_EDITAR")?.value,
	  PERMISO_ELIMINAR = document.querySelector("#PERMISO_ELIMINAR")?.value;
	const guardar_usuarios = (ev) => {
		ev.preventDefault();
		const BOTON = FORMULARIO_NUEVOS_USUARIOS.querySelector(`button[type="submit"]`);
		BOTON.disabled = true;
		const formData = new FormData(FORMULARIO_NUEVOS_USUARIOS);
		$.ajax({
		    "url": base_url("usuarios/guardar"),
		    "method": "POST",
		    "data": formData,
		    "dataType": "json",
		    "processData": false,
		    "contentType": false,
		    "beforeSend": function () { ; },
		}).done(( resp ) => {
			if (resp.TIPO === "SUCCESS") {
				FORMULARIO_NUEVOS_USUARIOS.reset();
				GENERAR_USUARIO_MODAL.querySelector(`[data-dismiss]`).click();
				USUARIOS_DATA_TABLE.ajax.reload(null, false);
			}
			alerta[capitalize(resp.TIPO)](resp.MENSAJE).show()
	    }).fail(function(err){
	        confirmar.Danger(`Se ha presentado un imprevisto al momento de procesar la solicitud :( \nError: ${err.responseText}`, null, true);
	        console.error(err);
	    }).always((resp) => {
			BOTON.disabled = false;
	    });
	}, actualizar_usuario = (ev) => {
		ev.preventDefault();
		const BOTON = FORMULARIO_NUEVOS_USUARIOS.querySelector(`button[type="submit"]`);
		if (!PERMISO_EDITAR)  { return; }
		BOTON.disabled = true;
		const formData = new FormData(FORMULARIO_ACTUALIZAR_USUARIOS);
		$.ajax({
		    "url": base_url("usuarios/editar"),
		    "method": "POST",
		    "data": formData,
		    "dataType": "json",
		    "processData": false,
		    "contentType": false,
		    "beforeSend": function () { ; },
		}).done(( resp ) => {
			if (resp.TIPO === "SUCCESS") {
				FORMULARIO_ACTUALIZAR_USUARIOS.reset();
				ACTUALIZAR_USUARIO_MODAL.querySelector(`[data-bs-dismiss]`).click();
				USUARIOS_DATA_TABLE.ajax.reload(null, false);
			}
			alerta[capitalize(resp.TIPO)](resp.MENSAJE).show()
	    }).fail(function(err){
	        confirmar.Danger(`Se ha presentado un imprevisto al momento de procesar la solicitud :( \nError: ${err.responseText}`, null, true);
	        console.error(err);
	    }).always((resp) => {
			BOTON.disabled = false;
	    });
	}, eliminar_usuario = (ev) => {
		const BOTON = ev.target.closest("button");
		confirmar.Danger("¿Está seguro de querer inactivar este registro?", "Atención!").then((resp) => {
			if (false !== resp) {
				BOTON.disabled = true;
				$.ajax({
				    "url": base_url("usuarios/eliminar"),
				    "method": "POST",
				    "data": { idusuario: BOTON.getAttribute("data-app-usuario-idusuario"), _method: "DELETE", },
				    "dataType": "json",
				    "beforeSend": function () { ; },
				}).done(( resp ) => {
					if (resp.TIPO === "SUCCESS") {
						USUARIOS_DATA_TABLE.ajax.reload(null, false);
					}
					alerta[capitalize(resp.TIPO)](resp.MENSAJE).show();
			    }).fail(function(err){
			        confirmar.Danger(`Se ha presentado un imprevisto al momento de procesar la solicitud :( \nError: ${err.responseText}`, null, true);
			        console.error(err);
			    }).always((resp) => {
					BOTON.disabled = false;
			    });
			}
		});
	}, obtener_usuario = (ev) => {
		const BOTON = ev.relatedTarget;
		BOTON.disabled = true;
		$.ajax({
		    "url": base_url("usuarios/obtener"),
		    "method": "GET",
		    "data": { idusuario: BOTON.getAttribute("data-app-usuario-idusuario") },
		    "dataType": "json",
		    "beforeSend": function () { ; },
		}).done(( resp ) => {
			Object.entries(resp).forEach(([key, value]) => {
				const el = ACTUALIZAR_USUARIO_MODAL.querySelector(`[name="${key}"]`);
				if (!el) { return; }
				el.value = value;
			});
	    }).fail(function(err){
	        alerta.Danger("Se ha presentado un imprevisto al momento de intentar obtener la información :( \nError:"+err.responseText).show()
	        console.error(err);
	    }).always((resp) => {
			BOTON.disabled = false;
	    });
	}

	const USUARIOS_DATA_TABLE = new DataTable(TABLA_USUARIOS, {
        dom: `
            <'dt-layout-row'<'dt-layout-cell dt-layout-start' B><'dt-layout-cell dt-layout-end' f>>
            <'dt-layout-row dt-layout-table' <'dt-layout-cell  dt-layout-full' t>>
            <'dt-layout-row'<'dt-layout-cell dt-layout-start' i><'dt-layout-cell dt-layout-end' p>>
        `,
		bLengthChange: false,
		pageLength: 25,
		ajax: {
			url: base_url('usuarios/obtener'),
			method: "GET",
			responseType: "json",
			data: function (d) {
				d.nombre = USUARIOS_FILTROS_CONTAINER.querySelector(`[data-app-filtro-nombre]`)?.value;
				d.usuario = USUARIOS_FILTROS_CONTAINER.querySelector(`[data-app-filtro-usuario]`)?.value;
				d.idtipo = USUARIOS_FILTROS_CONTAINER.querySelector(`[data-app-filtro-idtipo]`)?.value;
				d.estado = USUARIOS_FILTROS_CONTAINER.querySelector(`[data-app-filtro-estado]`)?.value;
			},
			// dataSrc: function(response) {
			//     console.log(response)
			//     return response.data; // No es necesario realizar ninguna transformación si el API ya devuelve un JSON válido
			// },
			error: function (response) {
				console.log(response)
				return response; // No es necesario realizar ninguna transformación si el API ya devuelve un JSON válido
			},
		},
		bAutoWidth: false,
		order: [[1, "ASC"]],
		columns: [
			{
				"targets": 0,
				"orderable": false,
				"class": "fs-5 p-1",
				"data": "ID_USUARIO",
				'render': function (data, type, row, meta) {
					let botones = ``;
	                if (PERMISO_EDITAR) {
	                	botones += `
							<button
								type="button"
								class="btn btn-primary btn-sm"
								data-bs-target="#actualizarUsuariosModal"
								data-bs-toggle="modal"
								data-app-usuario-idusuario="${data}"
							>
								<i class='bx bx-show-alt'></i>
							</button>
		                `;
	                }
	                if (PERMISO_ELIMINAR && row.ESTADO === "ACT") {
	                	botones += `
							<button
								type="button"
								class="btn btn-danger btn-sm"
								data-app-usuario-remover
								data-app-usuario-idusuario="${data}"
							>
								<i class='bx bx-trash-alt'></i>
							</button>
	                	`;
	                }
					return botones;
				}
			},
			{
				"targets": 1,
				"orderable": false,
				"class": "fs-5 p-1",
				"data": "NOMBRE",
				'render': function (data, type, row, meta) {
					return data
				}
			},
			{
				"targets": 2,
				"orderable": false,
				"class": "fs-5 p-1",
				"data": "USUARIO",
				'render': function (data, type, row, meta) {
					return data
				}
			},
			{
				"targets": 3,
				"orderable": false,
				"class": "fs-5 p-1",
				"data": "TIPO_USUARIO",
				'render': function (data, type, row, meta) {
					return data
				}
			},
			{
				"targets": 4,
				"orderable": false,
				"class": "fs-5 p-1",
				"data": "ESTADO",
				'render': function (data, type, row, meta) {
					return {"ACT": "ACTIVO", "INC": "INACTIVO"}[data];
				}
			},
		],
		language: {
			url: base_url("public/dist/datatables/language_esp.json")
		},
		initComplete: function () {
			const api = this.api()
			const filterContainer = document.querySelector("[data-app-filtros]");
	        function handleFilterEvent(event) {
	            const target = event.target;
	            if (!target.matches("[data-col-dt]")) { return }
	            const columnIndex = target.getAttribute("data-col-dt");
	            const value = target.value.trim();
	            USUARIOS_DATA_TABLE.column(columnIndex).search(value).draw();
	        }
			filterContainer.addEventListener("input", handleFilterEvent)
		},
		drawCallback: function () {
			$(`[data-app-usuario-remover]`).off("click").on("click", eliminar_usuario);
		}
	});
	window.addEventListener("load", () => {
		if (PERMISO_GUARDAR) { FORMULARIO_NUEVOS_USUARIOS.addEventListener("submit", guardar_usuarios); }
		if (PERMISO_EDITAR) { FORMULARIO_ACTUALIZAR_USUARIOS.addEventListener("submit", actualizar_usuario); }
		BOTON_BUSCAR_USUARIOS.addEventListener("click", () => USUARIOS_DATA_TABLE.ajax.reload(null, true));
		USUARIOS_FILTROS_CONTAINER.addEventListener("change", () => USUARIOS_DATA_TABLE.ajax.reload(null, true));
		$(ACTUALIZAR_USUARIO_MODAL).on("show.bs.modal", obtener_usuario);
	});
})();
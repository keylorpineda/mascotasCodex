(function () {
	const TABLA_SOLICITUDES_VACACIONES = document.querySelector(`[data-solicitudes-vacaciones-tabla]`),
	  FILTROS_CONTAINER = document.querySelector(`[data-app-filtros]`),
	  MODAL_SOLICITUD = document.querySelector(`#solicitudModal`);
	const rechazar_solicitud = (ev) => {
		const BOTON = ev.target.closest("button");
		confirmar.Danger("¿Está seguro de querer rechazar esta solicitud?", "Atención!").then((resp) => {
			if (false !== resp) {
				BOTON.disabled = true;
				$.ajax({
				    "url": base_url("vacaciones/rechazar"),
				    "method": "POST",
				    "data": { idvacacion: BOTON.getAttribute("data-solicitud-idvacacion"), _method: "DELETE", },
				    "dataType": "json",
				    "beforeSend": function () { ; },
				}).done(( resp ) => {
					if (resp.TIPO === "SUCCESS") {
						VACACIONES_DATA_TABLE.ajax.reload(() => Modal.Open(`#modalcargando`), false);
					}
					alerta[capitalize(resp["TIPO"])](resp["MENSAJE"]).show();
			    }).fail(function(err){
			        confirmar.Danger(`Se ha presentado un imprevisto al momento de procesar la solicitud :( \nError: ${err.responseText}`, null, true);
			        console.error(err);
			    }).always((resp) => {
					BOTON.disabled = false;
			    });
			}
		});
	}, aplicar_solicitud = (ev) => {
		const BOTON = ev.target.closest("button");
		confirmar.Info("¿Está seguro de querer aplicar esta solicitud?", "Atención!").then((resp) => {
			if (false !== resp) {
				BOTON.disabled = true;
				$.ajax({
				    "url": base_url("vacaciones/aplicar"),
				    "method": "POST",
				    "data": { idvacacion: BOTON.getAttribute("data-solicitud-idvacacion"), _method: "PUT", },
				    "dataType": "json",
				    "beforeSend": function () { ; },
				}).done(( resp ) => {
					if (resp.TIPO === "SUCCESS") {
						VACACIONES_DATA_TABLE.ajax.reload(() => Modal.Open(`#modalcargando`), false);
						MODAL_SOLICITUD.querySelector(`[data-bs-dismiss]`).click();
					}
					alerta[capitalize(resp["TIPO"])](resp["MENSAJE"]).show();
			    }).fail(function(err){
			        confirmar.Danger(`Se ha presentado un imprevisto al momento de procesar la solicitud :( \nError: ${err.responseText}`, null, true);
			        console.error(err);
			    }).always((resp) => {
					BOTON.disabled = false;
			    });
			}
		});
	}, obtener_datos_solicitudes = (ev) => {
		const BOTON = ev.relatedTarget;
		const TABLA_HISTORIAL_EMPLEADO = MODAL_SOLICITUD.querySelector(`[data-historial-empleado-tabla] tbody`);
		$.ajax({
		    "url": base_url("vacaciones/obtener"),
		    "method": "GET",
		    "data": { idvacacion: BOTON.getAttribute("data-solicitud-idvacacion"), },
		    "dataType": "json",
		    "beforeSend": function () { ; },
		}).done(( resp ) => {
			Object.entries(resp["SOLICITUD"]).forEach(([key, value]) => {
				const INPUT = MODAL_SOLICITUD.querySelector(`input[data-solicitud-${key}]`); 
				INPUT.value = value
			});
			const FILAS = resp["HISTORIAL"].map(render_fila_historial);
	    	if (FILAS.length === 0) { FILAS.push(`<tr><td colspan="8" class="text-center fw-bold">NO HAY DATOS PARA MOSTRAR</td></tr>`); }
	        TABLA_HISTORIAL_EMPLEADO.innerHTML = FILAS.join("");
			console.log(resp);
	    }).fail(function(err){
	        confirmar.Danger(`Se ha presentado un imprevisto al momento de procesar la solicitud :( \nError: ${err.responseText}`, null, true);
	        console.error(err);
	    }).always((resp) => {
	    	Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach((t) => new bootstrap.Tooltip(t));
	    });
	}, render_fila_historial = (SOLICITUD) => (
		`
			<tr ${SOLICITUD["ESTADO"] === "RECHAZADO" ? 'class="table-danger"' : ""}>
				<td>${SOLICITUD["ID_VACACION"]}</td>
				<td>${moment(SOLICITUD["CREADO_FECHA"]).format("DD/MM/YYYY HH:mm:ss")}</td>
				<td>${SOLICITUD["FECHA_RIGE"] ? moment(SOLICITUD["FECHA_RIGE"]).format("DD/MM/YYYY") : "---"}</td>
				<td>${SOLICITUD["FECHA_VENCE"] ? moment(SOLICITUD["FECHA_VENCE"]).format("DD/MM/YYYY") : "---"}</td>
				<td>${SOLICITUD["TIPO_SOLICITUD"]}</td>
				<td class="text-truncate" style="max-width: 200px;" data-bs-toggle="tooltip" title="${SOLICITUD["NOTA"]}">${SOLICITUD["NOTA"]}</td>
				<td>${SOLICITUD["MODIFICADO_USUARIO"]}</td>
				<td>${moment(SOLICITUD["MODIFICADO_FECHA"]).format("DD/MM/YYYY HH:mm:ss")}</td>
				<td>${SOLICITUD["ESTADO"]}</td>
			</tr>
		`
	);

	let VACACIONES_DATA_TABLE;
	window.addEventListener("load", () => {
		Modal.Open(`#modalcargando`);
		VACACIONES_DATA_TABLE = new DataTable(TABLA_SOLICITUDES_VACACIONES, {
	        dom: `
	            <'dt-layout-row'<'dt-layout-cell dt-layout-start' B><'dt-layout-cell dt-layout-end' f>>
	            <'dt-layout-row dt-layout-table' <'dt-layout-cell  dt-layout-full' t>>
	            <'dt-layout-row'<'dt-layout-cell dt-layout-start' i><'dt-layout-cell dt-layout-end' p>>
	        `,
			bLengthChange: false,
			pageLength: 25,
			ajax: {
				url: base_url('vacaciones/obtener'),
				method: "GET",
				responseType: "json",
				data: function (d) {
					d.nombre 	  = FILTROS_CONTAINER.querySelector(`[data-filtro-nombre]`)?.value;
					d.estado 	  = FILTROS_CONTAINER.querySelector(`[data-filtro-estado]`)?.value;
					d.fecharige   = FILTROS_CONTAINER.querySelector(`[data-filtro-fecharige]`)?.value;
					d.creadofecha = FILTROS_CONTAINER.querySelector(`[data-filtro-creadofecha]`)?.value;
				},
				dataSrc: function(response) {
				    console.log(response)
				    return response.data; // No es necesario realizar ninguna transformación si el API ya devuelve un JSON válido
				},
				error: function (response) {
					console.log(response)
					return response; // No es necesario realizar ninguna transformación si el API ya devuelve un JSON válido
				},
			},
			bAutoWidth: false,
			processing: true,
			order: [[1, "ASC"]],
			columns: [
				{
					"targets": 0,
					"orderable": false,
					"class": "fs-5 p-1",
					"data": "ID_VACACION",
					'render': function (data, type, row, meta) {
						let botones = `
							<button
								type="button"
								class="btn btn-primary btn-sm mr-1"
								data-bs-target="#solicitudModal"
								data-bs-toggle="modal"
								data-solicitud-idvacacion="${data}"
							>
								<i class='bx bx-show-alt'></i>
							</button>
		                `;
		                if (row["ESTADO"] === "PND") {
		                	botones += `
								<button
									type="button"
									class="btn btn-danger btn-sm"
									data-solicitud-remover
									data-solicitud-idvacacion="${data}"
								>
									<i class='bx bx-trash-alt'></i>
								</button>
		                	`;
		                }
						return `<div class="d-flex">${botones}</div>`;
					}
				},
				{
					"targets": 1,
					"orderable": false,
					"class": "fs-5 p-1 text-center",
					"data": "ID_VACACION",
					'render': function (data, type, row, meta) {
						return data
					}
				},
				{
					"targets": 2,
					"orderable": false,
					"class": "fs-5 p-1",
					"data": "NOMBRE",
					'render': function (data, type, row, meta) {
						return data
					}
				},
				{
					"targets": 3,
					"orderable": false,
					"class": "fs-5 p-1 text-center",
					"data": "FECHA_RIGE",
					'render': function (data, type, row, meta) {
						return data ? moment(data).format("DD/MM/YYYY") : "---"
					}
				},

				{
					"targets": 4,
					"orderable": false,
					"class": "fs-5 p-1 text-center",
					"data": "FECHA_VENCE",
					'render': function (data, type, row, meta) {
						return data ? moment(data).format("DD/MM/YYYY") : "---"
					}
				},

				{
					"targets": 5,
					"orderable": false,
					"class": "fs-5 p-1 text-center",
					"data": "TIPO_SOLICITUD",
					'render': function (data, type, row, meta) {
						return data
					}
				},

				{
					"targets": 6,
					"orderable": false,
					"class": "fs-5 p-1",
					"data": "CREADO_FECHA",
					'render': function (data, type, row, meta) {
						return moment(data).format("DD/MM/YYYY HH:mm:ss");
					}
				},
				{
					"targets": 7,
					"orderable": false,
					"class": "fs-5 p-1",
					"data": "ESTADO",
					'render': function (data, type, row, meta) {
						return `
							<span class="d-none">${data}</span>
							${{"PND": "PENDIENTE", "GST": "GESTIONADO", "RCH": "RECHAZADO"}[data]}
						`;
					}
				},
			],
			language: {
				url: base_url("public/dist/datatables/language_esp.json")
			},
			initComplete: function () {
				const api = this.api()
		        function handleFilterEvent(event) {
		            const target = event.target;
		            if (!target.matches("[data-col-dt]")) { return }
		            const columnIndex = target.getAttribute("data-col-dt");
		            const value = target.value.trim();
		            VACACIONES_DATA_TABLE.column(columnIndex).search(value).draw();
		        }
	            FILTROS_CONTAINER.addEventListener("input", handleFilterEvent);
	            FILTROS_CONTAINER.addEventListener("change", handleFilterEvent);
			},
			drawCallback: function () {
				setTimeout(() => Modal.Close(`#modalcargando`), 1000);
				$(`[data-solicitud-remover]`).off("click").on("click", rechazar_solicitud);
			}
		});
		MODAL_SOLICITUD.addEventListener("show.bs.modal", obtener_datos_solicitudes);
		MODAL_SOLICITUD.querySelector(`button[data-solicitud-aplicar]`).addEventListener("click", aplicar_solicitud);
		FILTROS_CONTAINER.querySelector(`[data-filtro-creadofecha]`).addEventListener("change", () => VACACIONES_DATA_TABLE.ajax.reload(() => Modal.Open(`#modalcargando`), false));
		FILTROS_CONTAINER.querySelector(`[data-filtro-buscar]`).addEventListener("click", () => VACACIONES_DATA_TABLE.ajax.reload(() => Modal.Open(`#modalcargando`), false));
		init_easepicker();
	});
})();
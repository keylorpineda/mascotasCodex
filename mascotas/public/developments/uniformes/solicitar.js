(function () {
	const FORMULARIO_SOLICITAR_UNIFORME = document.querySelector(`#FORMULARIO-SOLICITAR-UNIFORME`),
		OPCIONES_UBICACIONES = Array.from(document.querySelector(`#ubicaciones_datalist`).querySelectorAll(`option`)),
		OPCIONES_EMPLEADOS_FIJOS = Array.from(document.querySelector(`#F`).content.cloneNode(true).querySelectorAll(`option`)),
		OPCIONES_EMPLEADOS_SUSTITUTOS = Array.from(document.querySelector(`#S`).content.cloneNode(true).querySelectorAll(`option`));
	const guardar_datos_solicitud = (ev) => {
		ev.preventDefault();
		const formData = new FormData(FORMULARIO_SOLICITAR_UNIFORME);
		const TOTAL_PRENDAS = (
			parseInt(formData.get("CAMISAS[CANTIDAD]") 	  || 0) +
			parseInt(formData.get("PANTALONES[CANTIDAD]") || 0) +
			parseInt(formData.get("ZAPATOS[CANTIDAD]") 	  || 0)
		);
		const SIN_TALLAS = formData.get("CAMISAS[TALLA]").trim().length !== 0 ||
			formData.get("PANTALONES[TALLA]").trim().length !== 0 ||
			formData.get("ZAPATOS[TALLA]").trim().length !== 0;
		if (TOTAL_PRENDAS <= 0 || !SIN_TALLAS) {
			return confirmar.Warning("Es necesario solicitar al menos una prenda (Camisas, Pantolones, Zapatos) para realizar el registro", null, true);
		}
		const BOTON = FORMULARIO_SOLICITAR_UNIFORME.querySelector(`button[type="submit"]`);
		BOTON.disabled = true;
		Modal.Open("#modalcargando");
		return $.ajax({
		    "url": base_url("uniformes/guardar"),
		    "method": "POST",
		    "data": formData,
		    "dataType": "json",
		    "processData": false,
		    "contentType": false,
		    "beforeSend": function () { ; },
		}).done(( resp ) => {
			if (resp["TIPO"] === "SUCCESS") {
				alerta[capitalize(resp["TIPO"])](resp["MENSAJE"]).show();
				return FORMULARIO_SOLICITAR_UNIFORME.reset();
			}
			confirmar[capitalize(resp["TIPO"])](resp["MENSAJE"], null, true);
	    }).fail((err) => {
	        alerta.Danger("Se ha presentado un imprevisto al momento de intentar obtener la información :( "+err.responseText).show()
	        console.error(err);
	    }).always((resp) => {
			BOTON.disabled = false;
			setTimeout(() => Modal.Close("#modalcargando"), 1000);
	    })
	}, filtros_inputs_talla_cantidad = (ev) => {
		const INPUT = ev.target;
		const KEY = ev.keyCode;
		if (INPUT.matches(`[data-form-cantidad]`)) {
			if (
				// Permitir: backspace, delete, tab, escape y enter
				[8, 9, 13, 27, 46].includes(KEY) ||
		        // Permitir: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
		        (event.ctrlKey && [65, 67, 86, 88].includes(event.keyCode)) ||
		        // Permitir: números (0-9)
		        ((event.keyCode >= 48 && event.keyCode <= 57) || (event.keyCode >= 96 && event.keyCode <= 105))
			) { return; }
			ev.preventDefault();
		}
	}, mostrar_empleados_segun_tipo = (ev) => {
		const TIPO 				  = ev.target?.value.trim();
		const EMPLEADO 			  = FORMULARIO_SOLICITAR_UNIFORME.querySelector(`input[name="EMPLEADO"]`);
		const UBICACIONES 		  = FORMULARIO_SOLICITAR_UNIFORME.querySelector(`input[name="UBICACION"]`);
		const LISTA_EMPLEADOS 	  = { "F": OPCIONES_EMPLEADOS_FIJOS, "S": OPCIONES_EMPLEADOS_SUSTITUTOS, "R": null, }[TIPO];
		const CODIGO_EMPLEADO 	  = FORMULARIO_SOLICITAR_UNIFORME.querySelector(`input[name="CODIGO_EMPLEADO"]`);
		const CODIGO_UBICACIONES  = FORMULARIO_SOLICITAR_UNIFORME.querySelector(`input[name="CODIGO_UBICACION"]`);
		const EMPLEADOS_DATA_LIST = FORMULARIO_SOLICITAR_UNIFORME.querySelector(`#colaboradores_datalist`);

		if (LISTA_EMPLEADOS !== null) EMPLEADOS_DATA_LIST.replaceChildren(...LISTA_EMPLEADOS);
		CODIGO_EMPLEADO.value = CODIGO_UBICACIONES.value = "";
		EMPLEADO.value 		  = UBICACIONES.value 		 = "";
		EMPLEADO.disabled 	  = LISTA_EMPLEADOS === null;
		UBICACIONES.disabled  = false;
	}, especificar_empleado_ubicacion = (ev) => {
		if (timeout) { clearTimeout(timeout); }
		timeout = setTimeout(
			() => {
				timeout = null;
				const INPUT = ev.target;
				if (INPUT.value.trim().length === 0) { return; }
				let MATCH = "";

				const INPUT_CODIGO_UBICACION = FORMULARIO_SOLICITAR_UNIFORME.querySelector(`input[name="CODIGO_UBICACION"]`);
				const INPUT_CODIGO_EMPLEADO  = FORMULARIO_SOLICITAR_UNIFORME.querySelector(`input[name="CODIGO_EMPLEADO"]`);
				const INPUT_UBICACION 		 = FORMULARIO_SOLICITAR_UNIFORME.querySelector(`input[name="UBICACION"]`);
				const INPUT_TIPO 	  		 = FORMULARIO_SOLICITAR_UNIFORME.querySelector(`select[name="TIPO"]`);

				if (INPUT.matches(`input[name="UBICACION"]`)) {
					MATCH = autocomplete.findMatch(INPUT?.value.trim(), 'U').match;
					const CODIGO_UBICACION_MATCH = FORMULARIO_SOLICITAR_UNIFORME.querySelector(`#ubicaciones_datalist option[value="${MATCH}"]`);
					INPUT_CODIGO_UBICACION.value = CODIGO_UBICACION_MATCH.getAttribute(`data-empleado-ubicacion`);
					return INPUT.value = MATCH;
				}

				MATCH = autocomplete.findMatch(
					INPUT?.value.trim(), INPUT_TIPO?.value.trim()
				).match;
				const OPTION_EMPLEADO_MATCH  = FORMULARIO_SOLICITAR_UNIFORME.querySelector(`#colaboradores_datalist option[value="${MATCH}"]`);
				// if (INPUT_UBICACION.value.trim().length === 0) {
				if (INPUT_TIPO?.value.trim() === "S") {
					const OPTION_UBICACION 		 = OPCIONES_UBICACIONES.find(
						(o) => o.getAttribute(`data-empleado-ubicacion`) === OPTION_EMPLEADO_MATCH.getAttribute(`data-empleado-ubicacion`)
					);
					INPUT_CODIGO_UBICACION.value = OPTION_UBICACION?.getAttribute(`data-empleado-ubicacion`);
					INPUT_UBICACION.value 		 = OPTION_UBICACION?.value ?? "";
				}
				INPUT_CODIGO_EMPLEADO.value = OPTION_EMPLEADO_MATCH?.getAttribute(`data-empleado-codigo`);
				return INPUT.value = MATCH;
			},
			1000
		);
	};
	let timeout = null;
	const autocomplete = new SmartAutocomplete({ "debug": true, "minScore": 25, });
	autocomplete
	    .registerDataSource(
	    	'F',
	    	OPCIONES_EMPLEADOS_FIJOS.map((o) => o.value.trim())
	    )
	    .registerDataSource(
	    	'S',
	    	OPCIONES_EMPLEADOS_SUSTITUTOS.map((o) => o.value.trim())
	    )
	    .registerDataSource(
	    	'U',
	    	OPCIONES_UBICACIONES.map((o) => o.value.trim())
	    );
	window.addEventListener("load", () => {
		FORMULARIO_SOLICITAR_UNIFORME.addEventListener("submit", guardar_datos_solicitud);
		FORMULARIO_SOLICITAR_UNIFORME.querySelector(`select[name="TIPO"]`).addEventListener("change", mostrar_empleados_segun_tipo);
		FORMULARIO_SOLICITAR_UNIFORME.querySelectorAll(`input[name="EMPLEADO"], input[name="UBICACION"]`).forEach((i) => i.addEventListener("input", especificar_empleado_ubicacion));
	});
})();
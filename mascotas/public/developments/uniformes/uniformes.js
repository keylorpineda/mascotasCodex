(function () {
	const FORMULARIO_SOLICITAR_UNIFORME = document.querySelector(`#FORMULARIO-SOLICITAR-UNIFORME`);
	const guardar_datos_solicitud = (ev) => {
		ev.preventDefault();
		const BOTON = FORMULARIO_SOLICITAR_UNIFORME.querySelector(`button[type="submit"]`);
		BOTON.disabled = true;
		Modal.Open("#modalcargando");
		setTimeout(() => {
			Modal.Close("#modalcargando");
			alerta.Success("Solicitud realizada correctamente").show();
			BOTON.disabled = false;
			return FORMULARIO_SOLICITAR_UNIFORME.reset();
		}, 2000);
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
	};
	window.addEventListener("load", () => {
		FORMULARIO_SOLICITAR_UNIFORME.addEventListener("submit", guardar_datos_solicitud);
		FORMULARIO_SOLICITAR_UNIFORME.addEventListener("keydown", filtros_inputs_talla_cantidad);
	});
})();
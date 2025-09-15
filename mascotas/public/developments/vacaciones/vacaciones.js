(function () {
	const FORMULARIO_SOLICITAR_VACACION = document.querySelector(`#FORMULARIO-SOLICITAR-VACACION`);
	const guardar_datos_solicitud = (ev) => {
		ev.preventDefault();
		const BOTON = FORMULARIO_SOLICITAR_VACACION.querySelector(`button[type="submit"]`);
		BOTON.disabled = true;
		Modal.Open("#modalcargando");
		setTimeout(() => {
			Modal.Close("#modalcargando");
			alerta.Success("Solicitud realizada correctamente").show();
			BOTON.disabled = false;
			return FORMULARIO_SOLICITAR_VACACION.reset();
		}, 2000);
	};
	window.addEventListener("load", () => {
		FORMULARIO_SOLICITAR_VACACION.addEventListener("submit", guardar_datos_solicitud);
		init_easepicker();
	});
})();
(function () {
	const FORMULARIO_LOGIN = document.querySelector("#formlogin");
	const iniciar_sesion = (ev) => {
		ev.preventDefault();
		$.ajax({
		    "url": base_url("validar"),
		    "method": "POST",
		    "data": serialize("#formlogin"),
		    "dataType": "json",
		    "beforeSend": function () { ; },
		}).done(function( resp ) {
			alerta[capitalize(resp.TIPO)](resp.MENSAJE).show();
		}).fail(function(err) {
		    alerta.Danger('Se ha presentado un imprevisto al momento de procesar su solicitud :(. '+err.responseText).show();
		    console.error(err);
		})
	}
	window.addEventListener("load", function () {
		FORMULARIO_LOGIN.addEventListener("submit", iniciar_sesion);
	});
})()
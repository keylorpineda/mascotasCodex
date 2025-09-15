(function () {
	const obtener_permisos_usuario = () => {
		$.ajax({
		    "url": base_url("permisos/obtener"),
		    "method": "GET",
		    "data": { ID_USUARIO: document.querySelector("#ID_USUARIO")?.value, },
		    "dataType": "json",
		    "beforeSend": function () {
		    },
		}).done(( resp ) => {
			console.log(resp)
			const PERMISOS_INPUTS = document.querySelectorAll(`[name="permisos[]"]`)
			PERMISOS_INPUTS.forEach((input) => input.checked = false)
			PERMISOS_INPUTS.forEach((input) => input.checked = resp.indexOf(input.value) != -1)
	    }).fail(function(err){
	        alerta.Danger("Se ha presentado un imprevisto al momento de intentar obtener la información :( "+err.responseText).show()
	        console.error(err);
	    }).always((resp) => {
	    })
	}, guardar_permisos_usuario = (ev) => {
		const INPUT = ev.target.closest("input")
		$.ajax({
		    "url": base_url("permisos/guardar"),
		    "method": "POST",
		    "data": { PERMISO: INPUT?.value, ID_USUARIO: document.querySelector("#ID_USUARIO")?.value, CHECKED: INPUT.checked, },
		    "dataType": "json",
		    "beforeSend": function () {
		    },
		}).done(( resp ) => {
			alerta[capitalize(resp.TIPO)](resp.MENSAJE).show()
	    }).fail(function(err){
	        alerta.Danger("Se ha presentado un imprevisto al momento de intentar obtener la información :( "+err.responseText).show()
	        console.error(err);
	    }).always((resp) => {
	    })
	}


	window.addEventListener("load", () => {
		$("#ID_USUARIO").on("change", obtener_permisos_usuario);
		$("[data-app-control-permisos]").find("input:checkbox").on("change", guardar_permisos_usuario);
	});
})();
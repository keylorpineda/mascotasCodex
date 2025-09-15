const cerrar_sesion = () => {
	confirmar.Danger("¿Realmente desea cerrar la sesión?").then((resp) => {
		if (false !== resp) {
			window.location.href = base_url("logout");
		}
	});
}
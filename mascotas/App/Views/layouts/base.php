<?php ob_start(); ?>
<?php
$ROLE = "ADMINISTRADOR"; // model("Usuarios\TiposUsuariosModel")->where("ID_TIPO_USUARIO", get_cookie(COOKIE_ID_TIPO_USUARIO))->getFirstRow()->getNOMBRE();
helper("str_helper");
?>
<div class="sb-sidenav-menu-heading"><?= $ROLE ?></div>
<!-- MANTENIMIENTO -->
<?php
$rand = random_str(12);
if (validar_permiso([""])):
?>
	<a
		class="nav-link collapsed"
		href="javascript: void(0);"
		data-bs-toggle="collapse"
		data-bs-target="#collapse<?= $rand ?>"
		aria-expanded="false"
		aria-controls="collapse<?= $rand ?>">
		<div class="sb-nav-link-icon">
			<i class="fa-solid fa-user"></i>
		</div>
		USUARIOS
		<div class="sb-sidenav-collapse-arrow">
			<i class="fa-solid fa-angle-down"></i>
		</div>
	</a>
	<div
		class="collapse"
		id="collapse<?= $rand ?>"
		aria-labelledby="headingOne"
		data-bs-parent="#sidenavAccordion">
		<nav class="sb-sidenav-menu-nested nav">
			<?php if (validar_permiso(["U0001", "U0002", "U0003",])): ?>
				<a class="nav-link" href="<?= base_url("usuarios/listado") ?>">
					USUARIOS
				</a>
			<?php endif ?>
			<?php if (validar_permiso(["P0001",])): ?>
				<a class="nav-link" href="<?= base_url("usuarios/permisos") ?>">
					PERMISOS
				</a>
			<?php endif ?>
		</nav>
	</div>
<?php
endif;
?>
<?php $sidebar = ob_get_clean(); ?>
<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
	<meta name="description" content="" />
	<meta name="author" content="" />

	<meta http-equiv="Expires" content="0">
	<meta http-equiv="Last-Modified" content="0">
	<meta http-equiv="Cache-Control" content="no-cache, mustrevalidate">
	<meta http-equiv="Pragma" content="no-cache">
	<title> MASCOTAS - <?php renderSection('titulo', 'App'); ?></title>
	<link rel="icon" href="https://grupomasiza.com/wp-content/uploads/2020/07/cropped-favicon-32x32.png" sizes="32x32">
	<link href="<?= base_url("public/css/styles.css") ?>" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="<?= base_url("public/css/label.css") ?>">

	<link rel="stylesheet" href="<?= base_url("public/dist/bootstrap/css/bootstrap.min.css") ?>">
	<!-- choose one -->
	<link rel="stylesheet" href="<?= base_url("public/dist/fontawesome/css/all.min.css") ?>" />

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.0/css/boxicons.min.css" integrity="sha512-pVCM5+SN2+qwj36KonHToF2p1oIvoU3bsqxphdOIWMYmgr4ZqD3t5DjKvvetKhXGc/ZG5REYTT6ltKfExEei/Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.0/css/animations.min.css" integrity="sha512-GKHaATMc7acW6/GDGVyBhKV3rST+5rMjokVip0uTikmZHhdqFWC7fGBaq6+lf+DOS5BIO8eK6NcyBYUBCHUBXA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

	<link rel="stylesheet" type="text/css" href="<?= base_url("public/css/custom.css?v=1") ?>">

	<?php renderSection('head'); ?>
	<script type="text/javascript">
		const base_url = (url = "") => `<?= base_url() ?>${url}`
	</script>
</head>

<body class="sb-nav-fixed">
	<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
		<a href="<?= base_url("inicio") ?>" class="navbar-brand">MASCOTAS</a>
		<button class="btn btn-link btn-sm order-1 order-lg-0" id="sidebarToggle" href="#">
			<i class="fas fa-bars"></i>
		</button>
		<?php renderSection('bc'); ?>
		<form class="d-none d-md-inline-block form-inline ml-auto mr-0 mr-md-3 my-2 my-md-0"></form>
		<ul class="navbar-nav ml-auto ml-md-0">
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle" id="userDropdown" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
					<i class="fas fa-user fa-fw"></i>
				</a>
				<div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
					<!--<a class="dropdown-item" href="#">Configuración</a><a class="dropdown-item" href="#">Usuario</a>
            <div class="dropdown-divider"></div>-->
					<a class="dropdown-item" href="javascript: cerrar_sesion();">Cerrar Sesión</a>
				</div>
			</li>
		</ul>
	</nav>

	<div id="layoutSidenav">
		<div id="layoutSidenav_nav">
			<nav class="sb-sidenav accordion sb-sidenav-dark show" id="sidenavAccordion">
				<div id="layoutSidenav_nav">
					<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
						<div class="sb-sidenav-menu">
							<div class="nav">
								<div class="sb-sidenav-menu-heading text-center">
									<h2 class="fw-bold">MASCOTAS</h2>
								</div>
								<a class="nav-link" href="<?= base_url("inicio") ?>">
									<div class="sb-nav-link-icon">
										<i class="fas fa-chart-bar"></i>
									</div>
									INICIO
								</a>
								<?= $sidebar ?>
							</div>
						</div>
					</nav>
				</div>
			</nav>
		</div>
		<div id="layoutSidenav_content">
			<!-- Aquí inicia el contenido -->
			<?php renderSection('body'); ?>
			<!-- Aquí termina el contenido -->
			<footer class="py-4 bg-light mt-auto">
				<div class="container-fluid">
					<div class="d-flex align-items-center justify-content-between small">
						<div class="text-muted">Copyright &copy; MASCOTAS <?= date('Y') ?></div>
					</div>
				</div>
			</footer>
		</div>
	</div>

	<!-- ======= Toast ======= -->
	<div class="position-fixed top-0 right-0 p-3" style="z-index: 10000; right: 0; top: 0; display: none;">
		<div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
			<div class="toast-header">
				<i></i>
				<strong class="mr-auto text-white"></strong>
				<button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="toast-body"></div>
		</div>
	</div>
	<!-- End Toast -->

	<!-- ======= Confirm ======= -->
	<div class="modal" id="confirmar" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header" data-confirm-type>
					<h5 class="modal-title" data-confirm-title></h5>
					<button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal" aria-label="Close" data-confirm-cancel>
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<h4 class="w-100 text-center" data-confirm-message></h4>
					</div>
				</div>
				<div class="modal-footer">
					<div class="w-100 d-none" data-confirm-botones-estilo-alerta>
						<button type="button" class="btn w-100 btn-danger" data-confirm-cancel data-bs-dismiss="modal">Cerrar</button>
					</div>
					<div class="row w-100" data-confirm-botones-estilo-confirmar>
						<div class="col-md-6 col-sm-6">
							<button type="button" class="btn w-100" data-confirm-accept data-bs-dismiss="modal">Aceptar</button>
						</div>
						<div class="col-md-6 col-sm-6">
							<button type="button" class="btn btn-danger w-100" data-confirm-cancel data-bs-dismiss="modal">Cancelar</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- End Confirm -->

	<div id="reportes" class="d-none"></div>

	<div class="modal fade" id="modalcargando" tabindex="-1" data-backdrop="static" data-keyboard="false" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body text-center">
					<i class="fa-solid fa-spinner fa-5x fa-spin"></i>
					<h4>Cargando...</h4>
					<h5>Por favor espera...</h5>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="reportesModal" tabindex="-1" data-backdrop="static" data-keyboard="false" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header" style="background-color: #cfe2ff;">
					<h3>Reportes</h3>
				</div>
				<div class="modal-body text-center">
					<?php renderSection('reportes', 'No hay reportes...'); ?>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript" src="<?= base_url("public/dist/jquery.min.js") ?>"></script>
	<script type="text/javascript" src="<?= base_url("public/dist/bootstrap/js/bootstrap.bundle.min.js") ?>"></script>
	<script type="text/javascript" src="<?= base_url("public/dist/helpers/helpers.js") ?>"></script>
	<script type="text/javascript" src="<?= base_url("public/dist/alerts/confirm.js") ?>"></script>
	<script type="text/javascript" src="<?= base_url("public/dist/alerts/toast.js") ?>"></script>

	<script type="text/javascript" src="<?= base_url("public/js/scripts.js") ?>"></script>
	<script type="text/javascript" src="<?= base_url("public/js/base.js") ?>"></script>
	<script type="text/javascript">
		const Modal = {
			"Open": (ID_MODAL) => {
				return new Promise((resolve, reject) => {
					try {
						const modalElement = document.querySelector(ID_MODAL);

						if (!modalElement) {
							reject(new Error(`Modal ${ID_MODAL} no encontrado`));
							return;
						}
						// Limpiar listeners previos para evitar duplicados
						$(modalElement).off('shown.bs.modal');
						$(modalElement).off('show.bs.modal');
						// Escuchar el evento cuando el modal se haya mostrado completamente
						const handleShown = () => {
							$(modalElement).off('shown.bs.modal', handleShown);
							resolve(modalElement);
						};
						$(modalElement).on('shown.bs.modal', handleShown);
						// Crear y ejecutar el trigger
						const trigger = document.createElement("a");
						trigger.setAttribute("data-target", ID_MODAL);
						trigger.setAttribute("data-toggle", "modal");
						trigger.setAttribute("class", "d-none");
						trigger.style.display = "none"; // Extra seguridad para ocultarlo

						document.body.appendChild(trigger);

						// Pequeño delay para asegurar que el elemento esté en el DOM
						setTimeout(() => {
							trigger.click();
							document.body.removeChild(trigger);
						}, 10);
					} catch (error) {
						reject(error);
					}
				});
			},
			"Close": (ID_MODAL) => {
				return new Promise((resolve, reject) => {
					try {
						const modalElement = document.querySelector(ID_MODAL);

						if (!modalElement) {
							return reject(new Error(`Modal ${ID_MODAL} no encontrado`));
						}
						// Limpiar listeners previos
						$(modalElement).off('hidden.bs.modal');
						// Escuchar el evento cuando el modal se haya ocultado completamente
						const handleHidden = () => {
							$(modalElement).off('hidden.bs.modal', handleHidden);
							return resolve(modalElement);
						};
						$(modalElement).on('hidden.bs.modal', handleHidden);
						const trigger = document.createElement("button");
						trigger.setAttribute("data-dismiss", "modal");
						trigger.setAttribute("class", "d-none");
						trigger.style.display = "none";

						modalElement.appendChild(trigger);
						trigger.click();
						return modalElement.removeChild(trigger);
					} catch (error) {
						reject(error);
					}
				});
			},
			"IsShow": (ID_MODAL) => {
				try {
					const modalElement = document.querySelector(ID_MODAL);

					if (!modalElement) {
						throw new Error(`Modal ${ID_MODAL} no encontrado`);
					}

					// Para Bootstrap 4: verificar usando jQuery data
					const $modal = $(modalElement);
					const bootstrapModal = $modal.data('bs.modal');

					// Si existe la instancia de Bootstrap 4, verificar su estado
					if (bootstrapModal && bootstrapModal._isShown) {
						return true;
					}

					// Método alternativo: verificar las clases y estilos
					const hasShowClass = modalElement.classList.contains('show');
					const isDisplayed = window.getComputedStyle(modalElement).display === 'block';
					const ariaHidden = modalElement.getAttribute('aria-hidden') === 'false';

					// En Bootstrap 4, verificar también si tiene el backdrop
					const hasBackdrop = document.querySelector('.modal-backdrop.show') !== null;

					return hasShowClass && isDisplayed && ariaHidden;

				} catch (error) {
					console.error('Error al verificar el estado del modal:', error);
					return false;
				}
			},
			"IsTransitioning": (ID_MODAL) => {
				try {
					const modalElement = document.querySelector(ID_MODAL);

					if (!modalElement) {
						return false;
					}

					const $modal = $(modalElement);
					const bootstrapModal = $modal.data('bs.modal');

					// Bootstrap 4: verificar si está en transición
					if (bootstrapModal && bootstrapModal._isTransitioning) {
						return true;
					}

					// Fallback: verificar eventos activos
					// En Bootstrap 4, durante la transición el modal tiene eventos pendientes
					const eventData = $._data(modalElement, 'events');
					if (eventData && (eventData['show.bs.modal'] || eventData['hide.bs.modal'])) {
						return true;
					}

					// Verificar transiciones CSS manualmente
					const hasFadeClass = modalElement.classList.contains('fade');
					const hasShowClass = modalElement.classList.contains('show');
					const isDisplayed = window.getComputedStyle(modalElement).display === 'block';

					// Estados de transición típicos en Bootstrap 4
					return hasFadeClass && (
						(isDisplayed && !hasShowClass) || // Abriéndose
						(!isDisplayed && hasShowClass) // Cerrándose
					);

				} catch (error) {
					console.error('Error al verificar transición del modal:', error);
					return false;
				}
			},
			"GetState": (ID_MODAL) => {
				try {
					const modalElement = document.querySelector(ID_MODAL);

					if (!modalElement) {
						return 'not_found';
					}

					const $modal = $(modalElement);
					const bootstrapModal = $modal.data('bs.modal');

					// Bootstrap 4: usar la instancia si existe
					if (bootstrapModal) {
						if (bootstrapModal._isTransitioning) {
							return bootstrapModal._isShown ? 'closing' : 'opening';
						}
						return bootstrapModal._isShown ? 'shown' : 'hidden';
					}

					// Fallback para Bootstrap 4 sin instancia
					const hasShowClass = modalElement.classList.contains('show');
					const isDisplayed = window.getComputedStyle(modalElement).display === 'block';

					if (Modal.IsTransitioning(ID_MODAL)) {
						return hasShowClass ? 'closing' : 'opening';
					}

					return hasShowClass && isDisplayed ? 'shown' : 'hidden';

				} catch (error) {
					console.error('Error al obtener el estado del modal:', error);
					return 'error';
				}
			}
		};
		<?php
		$ALERTA = session("ALERTA");
		if (isset($ALERTA)):
		?>
			alerta[`<?= strToCapitalize($ALERTA->getTIPO()) ?>`](`<?= $ALERTA->getMENSAJE() ?>`).show();
		<?php
		endif;
		?>
	</script>
	<?php renderSection('foot'); ?>

</body>

</html>
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

	    <link rel="icon" href="https://grupomasiza.com/wp-content/uploads/2020/07/cropped-favicon-32x32.png" sizes="32x32">
	    <title><?= NOMBRE_APP ?> - Login</title>
	    <link rel="stylesheet" href="<?= base_url("public/dist/bootstrap/css/bootstrap.min.css") ?>" />
	    <link rel="stylesheet" href="<?= base_url("public/dist/fontawesome/css/all.min.css") ?>" />
	    <link rel="stylesheet" href="<?= base_url("public/css/Login.css") ?>" />
	    <script type="text/javascript">
	        const base_url = (url = "") => `<?= base_url() ?>${url}`
	    </script>
	</head>
	<body>
	    <div class="login-container">
	        <div class="login-logo">
	            <img src="<?= base_url("public/img/logo.png") ?>" alt="Logo" />
	        </div>
	        <h1 class="login-title">Bienvenido(a)</h1>
	        <form id="formlogin" action="<?= base_url("login/validar") ?>" method="POST">
	            <input type="hidden" name="CSRF_TOKEN" value="<?= $TOKEN_CSRF ?>"/>
	            <input type="hidden" name="IGNORAR" value="" />
	            
	            <div class="form-floating mb-3">
	                <input type="text" class="form-control" id="user" name="user" placeholder="Usuario" required>
	                <label for="user"><i class="fa-solid fa-user me-2"></i>Usuario</label>
	            </div>
	            
	            <div class="form-floating mb-4">
	                <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
	                <label for="password"><i class="fa-solid fa-lock me-2"></i>Contraseña</label>
	                <div class="position-relative">
	                    <a href="#" class="position-absolute end-0 top-50 translate-middle-y me-3 text-muted" id="togglePassword">
	                        <i class="fa-solid fa-eye"></i>
	                    </a>
	                </div>
	            </div>
	            
	            <button type="submit" class="login-btn">
	                <i class="fa-solid fa-sign-in-alt me-2"></i>Ingresar
	            </button>
	        </form>
	    </div>

	    <!-- ======= Confirm ======= -->
	    <div class="modal fade" id="confirmar" tabindex="-1" aria-labelledby="confirmarLabel" aria-hidden="true">
	        <div class="modal-dialog modal-dialog-centered">
	            <div class="modal-content">
	                <div class="modal-header" data-confirm-type>
	                    <h2 class="modal-title fs-2" data-confirm-title id="confirmarLabel">Modal title</h2>
	                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" data-confirm-cancel></button>
	                </div>
	                <div class="modal-body">
	                    <div class="mb-3">
	                        <h4 class="w-100 text-center" data-confirm-message></h4>
	                    </div>
	                </div>
	                <div class="modal-footer">
	                    <div class="col-md-12" data-confirm-botones-estilo-alerta>
	                        <button type="button" class="btn w-100 btn-danger" data-confirm-cancel data-bs-dismiss="modal">Cerrar</button>
	                    </div>
	                    <div class="row col-md-12" data-confirm-botones-estilo-confirmar>
	                        <div class="col-md-6 col-sm-6">
	                            <button type="button" class="btn w-100 btn-primary" data-confirm-accept data-bs-dismiss="modal">Aceptar</button>
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

	    <script type="text/javascript" src="<?= base_url("public/dist/jquery.min.js") ?>"></script>
	    <script type="text/javascript" src="<?= base_url("public/dist/bootstrap/js/bootstrap.min.js") ?>"></script>
	    <script type="text/javascript" src="<?= base_url("public/dist/helpers/helpers.js") ?>"></script>
	    <script type="text/javascript" src="<?= base_url("public/dist/alerts/confirm.js") ?>"></script>
	    
	    <script>
	        // Toggle password visibility
	        document.getElementById('togglePassword').addEventListener('click', function (e) {
	            e.preventDefault();
	            const passwordInput = document.getElementById('password');
	            const icon = this.querySelector('i');
	            
	            if (passwordInput.type === 'password') {
	                passwordInput.type = 'text';
	                icon.classList.remove('fa-eye');
	                icon.classList.add('fa-eye-slash');
	            } else {
	                passwordInput.type = 'password';
	                icon.classList.remove('fa-eye-slash');
	                icon.classList.add('fa-eye');
	            }
	        });
	    </script>

	    <?php
	        if (isset($ALERTA)):
	            helper("str_helper");
	            ?>
	                <script type="text/javascript">
	                    confirmar[<?= json_encode(strToCapitalize($ALERTA->getTIPO())) ?>](<?= json_encode($ALERTA->getMENSAJE()) ?>, null, true)
	                </script>
	            <?php
	        endif;
	    ?>
	</body>
</html>
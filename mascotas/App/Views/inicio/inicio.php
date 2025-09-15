<?php layout("base") ?>

<?php section("head") ?>
<?php endSection() ?>

<?php section("bc") ?>
    <ol class="breadcrumb">
        <li class="breadcrumb-item active" aria-current="page">Inicio</li>
    </ol>
<?php endSection() ?>

<?php section("body") ?>
	<main>
		<div class="container-fluid mt-3">
			<div class="row">
				<?php if (validar_permiso([""])): ?>
					<div class="col-md-4">
	                    <a href="<?= base_url("usuarios") ?>" class="option-menu">
	                        <div class="card w-100 bg-danger-50">
	                            <div class="card-header text-center">
	                                <i class="fa-solid fa-user fa-6x"></i>
	                            </div>
	                            <div class="card-body p-1">
	                                <h6 class="text-center">Usuarios</h6>
	                            </div>
	                        </div>
	                    </a>
					</div>
				<?php endif ?>
			</div>
		</div>
	</main>
<?php endSection() ?>

<?php section("foot") ?>
	<script type="text/javascript" src="<?= base_url("public/developments/inicio/inicio.js?v=").rand() ?>"></script>
<?php endSection() ?>
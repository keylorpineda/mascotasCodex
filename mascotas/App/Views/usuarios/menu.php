<?php layout("base") ?>

<?php section("titulo") ?>
    Usuarios
<?php endSection() ?>

<?php section("head") ?>
<?php endSection() ?>

<?php section("bc") ?>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= base_url("inicio") ?>">Inicio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Usuarios</li>
    </ol>
<?php endSection() ?>

<?php section("body") ?>
    <main>
        <div class="container-fluid mt-3">
            <div class="row">
                <?php if (validar_permiso([""])): ?>
                    <div class="col-xl-3 col-md-4 col-6">
                        <a href="<?= base_url("usuarios/listado") ?>" class="option-menu m-1">
                            <div class="card w-100 bg-info-25">
                                <div class="card-header text-center">
                                    <i class="fa-solid fa-user-plus fa-6x"></i>
                                </div>
                                <div class="card-body p-1">
                                    <h6 class="text-center">Listado</h6>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endif ?>
                <?php if (validar_permiso([""])): ?>
                    <div class="col-xl-3 col-md-4 col-6">
                        <a href="<?= base_url("usuarios/permisos") ?>" class="option-menu m-1">
                            <div class="card w-100 bg-warning-25">
                                <div class="card-header text-center">
                                    <i class="fa-solid fa-list-check fa-6x"></i>
                                </div>
                                <div class="card-body p-1">
                                    <h6 class="text-center">Permisos Usuario</h6>
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
<?php endSection() ?>
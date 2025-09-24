<?php
$permiso_guardar  = validar_permiso("");
$permiso_eliminar = validar_permiso("");
$permiso_editar   = validar_permiso("");
//$permiso_guardar  = validar_permiso(['M0001']);
//$permiso_eliminar = validar_permiso(['M0003']);
//$permiso_editar   = validar_permiso(['M0002']);
?>

<?php layout('base') ?>

<?php section('titulo') ?>Mascotas<?php endSection() ?>

<?php section('head') ?>
<link rel="stylesheet" href="<?= base_url('public/dist/datatables/datatables.min.css') ?>" />
<?php endSection() ?>

<?php section('bc') ?>
<ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="<?= base_url('inicio') ?>">Inicio</a></li>
  <li class="breadcrumb-item active">Mascotas</li>
</ol>
<?php endSection() ?>

<?php section('body') ?>
<input type="hidden" id="PERMISO_GUARDAR" value="<?= $permiso_guardar ?>" />
<input type="hidden" id="PERMISO_EDITAR" value="<?= $permiso_editar ?>" />
<input type="hidden" id="PERMISO_ELIMINAR" value="<?= $permiso_eliminar ?>" />

<main>
  <div class="container-fluid">
    <div class="row mt-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header text-center" style="background: linear-gradient(to right, #20B2AA, #00FA9A, #20B2AA)">
            <h3 class="card-title mb-0">Gestión Integral de Mascotas y Propietarios</h3>
          </div>
          <div class="card-body">
            <div class="row mb-3" data-app-filtros>
              <div class="col-sm-4 col-lg-3">
                <label class="w-100">
                  Nombre Mascota:
                  <input type="text" class="form-control" placeholder="Nombre..." data-app-filtro-nombre />
                </label>
              </div>
              <div class="col-sm-4 col-lg-3">
                <label class="w-100">
                  Cédula Dueño:
                  <input type="text" class="form-control" placeholder="Cédula..." data-app-filtro-cedula data-mask-cedula />
                </label>
              </div>
              <div class="col-sm-4 col-lg-2">
                <label class="w-100">
                  Estado:
                  <select class="form-select" data-app-filtro-estado>
                    <option value="ACT" selected>Activo</option>
                    <option value="INC">Inactivo</option>
                    <option value="">Todos</option>
                  </select>
                </label>
              </div>
              <div class="col-sm-12 col-lg-4 d-flex align-items-end justify-content-end">
                <button type="button" class="btn btn-primary btn-icon me-2" data-app-filtro-buscar>
                  <i class='bx bx-search-alt-2'></i> Buscar
                </button>
                <?php if ($permiso_guardar): ?>
                  <button type="button" class="btn btn-success btn-icon" data-bs-toggle="modal" data-bs-target="#mascotaModal">
                    <i class="fas fa-plus"></i> Nueva Mascota
                  </button>
                <?php endif ?>
              </div>
            </div>

            <table id="tmascotas" class="table table-hover w-100"></table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php if ($permiso_guardar || $permiso_editar): ?>
  <div class="modal fade" id="mascotaModal" tabindex="-1" aria-labelledby="mascotaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header" style="background-color: #d2f4ea;">
          <h5 class="modal-title" id="mascotaModalLabel">Registrar / Editar Mascota</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <form id="FORM_MASCOTA" autocomplete="off" enctype="multipart/form-data">
          <div class="modal-body">
            <input type="hidden" name="ID_MASCOTA" />
            <div class="row g-3">
              <input type="hidden" name="ESTADO" value="ACT" data-app-estado-hidden />
              <div class="col-sm-4">
                <label class="w-100">
                  Dueño (cédula): <span class="text-danger">*</span>
                  <input type="text" class="form-control" name="ID_PERSONA" required data-mask-cedula />
                </label>
              </div>
              <div class="col-sm-4" data-duenno-field>
                <label class="w-100">
                  Nombre del dueño: <span class="text-danger">*</span>
                  <input type="text" class="form-control" name="NOMBRE_DUENNO" />
                </label>
              </div>
              <div class="col-sm-4" data-duenno-field>
                <label class="w-100">
                  Teléfono del dueño: <span class="text-danger">*</span>
                  <input type="text" class="form-control" name="TELEFONO_DUENNO" />
                </label>
              </div>
              <div class="col-sm-4" data-duenno-field>
                <label class="w-100">
                  Correo del dueño: <span class="text-danger">*</span>
                  <input type="email" class="form-control" name="CORREO_DUENNO" data-mask-email />
                </label>
              </div>
              <div class="col-sm-4">
                <label class="w-100">
                  Nombre Mascota: <span class="text-danger">*</span>
                  <input type="text" class="form-control" name="NOMBRE_MASCOTA" required />
                </label>
              </div>
              <div class="col-sm-6">
                <label class="w-100">
                  Foto (URL externa):
                  <input type="url" class="form-control" name="FOTO_URL" placeholder="https://..." data-app-foto-url />
                </label>
              </div>
              <div class="col-sm-6">
                <label class="w-100">
                  Foto (archivo local):
                  <input type="file" class="form-control" name="FOTO_ARCHIVO" accept="image/*" data-app-foto-archivo />
                </label>
              </div>
              <div class="col-sm-12 text-center">
                <input type="hidden" name="FOTO_ACTUAL" value="" data-app-foto-actual />
                <div class="d-inline-block">
                  <img src="" alt="Vista previa" class="img-thumbnail d-none" style="max-height: 140px;" data-app-foto-preview />
                </div>
              </div>
              <div class="col-sm-4 d-none" data-app-estado-select-container>
                <label class="w-100">
                  Estado: <span class="text-danger">*</span>
                  <select class="form-select" name="ESTADO" required disabled data-app-estado-select>
                    <option value="">Seleccione</option>
                    <option value="ACT">Activo</option>
                    <option value="INC">Inactivo</option>
                  </select>
                </label>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif ?>
<?php endSection() ?>

<?php section('foot') ?>
<script>
  const URL_MASCOTAS = {
    obtener: "<?= base_url('mascotas/obtener') ?>",
    guardar: "<?= base_url('mascotas/guardar') ?>",
    editar: "<?= base_url('mascotas/editar') ?>",
    eliminar: "<?= base_url('mascotas/eliminar') ?>"
  };
  const URL_PERSONAS = {
    buscar: "<?= base_url('personas/buscar-por-cedula') ?>"
  };
</script>
<script src="<?= base_url('public/dist/datatables/datatables.min.js') ?>"></script>
<script src="<?= base_url('public/js/form-masks.js') ?>"></script>
<script src="<?= base_url('public/js/mascotas.js') ?>"></script>
<?php endSection() ?>
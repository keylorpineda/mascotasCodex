<?php
$permiso_guardar  = validar_permiso("");
$permiso_eliminar = validar_permiso("");
$permiso_editar   = validar_permiso("");
//$permiso_guardar  = validar_permiso(['M0001']);
//$permiso_eliminar = validar_permiso(['M0003']);
//$permiso_editar   = validar_permiso(['M0002']);
?>

<?php layout('base') ?>

<?php section('titulo') ?>Gestión de Mascotas<?php endSection() ?>

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
        <div class="card shadow-lg border-0">
          <div class="card-header py-3" style="background: linear-gradient(to right, #20B2AA, #00FA9A, #20B2AA)">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div class="d-flex align-items-center gap-3">
                <span class="badge bg-white text-success rounded-circle p-3 shadow-sm">
                  <i class='bx bxs-dog fs-4'></i>
                </span>
                <div class="text-white">
                  <h3 class="card-title mb-1">Gestión de Mascotas</h3>
                </div>
              </div>
              <?php if ($permiso_guardar): ?>
                <button type="button" class="btn btn-light text-success fw-semibold" data-bs-toggle="modal" data-bs-target="#mascotaModal">
                  <i class="fas fa-plus me-1"></i> Nueva Mascota
                </button>
              <?php endif ?>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive shadow-sm rounded">
              <table id="tmascotas" class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                  <tr>
                    <th>ID</th>
                    <th>Mascota</th>
                    <th>Dueño</th>
                    <th>Foto</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<div class="modal fade" id="mascotaFotoPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title text-primary"><i class='bx bx-image-alt me-2'></i>Vista de fotografía</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" alt="Fotografía de la mascota" class="img-fluid rounded shadow-sm" data-modal-foto />
      </div>
    </div>
  </div>
</div>

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
            <input type="hidden" name="FOTO_ACTUAL" />
            <div class="row g-3">
              <input type="hidden" name="ESTADO" value="ACT" data-app-estado-hidden />
              <div class="col-sm-4">
                <label class="w-100">
                  Dueño (cédula): <span class="text-danger">*</span>
                  <input type="text" class="form-control" name="ID_PERSONA" required />
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
                  Adjuntar fotografía (JPG, PNG o WEBP):
                  <input type="file" class="form-control" name="FOTO_ARCHIVO" accept="image/jpeg,image/png,image/webp" />
                </label>
                <small class="text-muted">Si cargas una imagen desde tu equipo, se almacenará y se usará automáticamente.</small>
              </div>
              <div class="col-sm-12" data-foto-preview-container>
                <figure class="text-center d-none" data-foto-preview>
                  <img src="" alt="Vista previa de la mascota" class="img-fluid rounded shadow-sm" style="max-height: 200px;" />
                  <figcaption class="mt-2 text-muted">Vista previa de la imagen seleccionada</figcaption>
                </figure>
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
  const URL_IMAGEN_DEFAULT = "<?= base_url('public/dist/img/default-mascota.svg') ?>";
</script>
<script src="<?= base_url('public/dist/datatables/datatables.min.js') ?>"></script>
<script src="<?= base_url('public/js/form-masks.js') ?>"></script>
<script src="<?= base_url('public/developments/mascotas/mascotas.js') ?>"></script>
<?php endSection() ?>
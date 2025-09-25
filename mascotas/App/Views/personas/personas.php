<?php
  $permiso_guardar  = validar_permiso("");
  $permiso_eliminar = validar_permiso("");
  $permiso_editar   = validar_permiso("");
?>

<?php layout('base') ?>

<?php section('titulo') ?>Gestión de Personas<?php endSection() ?>

<?php section('head') ?>
<link rel="stylesheet" href="<?= base_url('public/dist/datatables/datatables.min.css') ?>" />
<?php endSection() ?>

<?php section('bc') ?>
<ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="<?= base_url('inicio') ?>">Inicio</a></li>
  <li class="breadcrumb-item active">Personas</li>
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
                  <i class='bx bxs-user-detail fs-4'></i>
                </span>
                <div class="text-white">
                  <h3 class="card-title mb-1">Gestión de Personas</h3>
                </div>
              </div>
              <?php if ($permiso_guardar): ?>
                <button type="button" class="btn btn-light text-success fw-semibold" data-bs-toggle="modal" data-bs-target="#personaCrearModal">
                  <i class="fas fa-plus me-1"></i> Nueva Persona
                </button>
              <?php endif ?>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive shadow-sm rounded">
              <table id="tpersonas" class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                  <tr>
                    <th>Cédula</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
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

<?php if ($permiso_guardar): ?>
<div class="modal fade" id="personaCrearModal" tabindex="-1" aria-labelledby="personaCrearModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #d2f4ea;">
       <h5 class="modal-title" id="personaCrearModalLabel">Registrar Persona</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
     <form id="FORM_PERSONA_CREAR" autocomplete="off">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-sm-4">
              <label class="w-100">
                Cédula: <span class="text-danger">*</span>
                <input type="text" class="form-control" name="ID_PERSONA" required />
              </label>
            </div>
            <div class="col-sm-8">
              <label class="w-100">
                Nombre: <span class="text-danger">*</span>
                <input type="text" class="form-control" name="NOMBRE" required />
              </label>
            </div>
            <div class="col-sm-6">
              <label class="w-100">
                Teléfono:
                <input type="text" class="form-control" name="TELEFONO" />
              </label>
            </div>
            <div class="col-sm-6">
              <label class="w-100">
                Correo:
                <input type="email" class="form-control" name="CORREO" data-mask-email />
              </label>
            </div>
            <div class="col-sm-12">
              <input type="hidden" name="ESTADO" value="ACT" />
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
<?php if ($permiso_editar): ?>
<div class="modal fade" id="personaEditarModal" tabindex="-1" aria-labelledby="personaEditarModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #d2f4ea;">
        <h5 class="modal-title" id="personaEditarModalLabel">Editar Persona</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form id="FORM_PERSONA_EDITAR" autocomplete="off">
        <div class="modal-body">
          <input type="hidden" name="ID" />
          <div class="row g-3">
            <div class="col-sm-4">
              <label class="w-100">
                Cédula: <span class="text-danger">*</span>
                <input type="text" class="form-control" name="ID_PERSONA" required />
              </label>
            </div>
            <div class="col-sm-8">
              <label class="w-100">
                Nombre: <span class="text-danger">*</span>
                <input type="text" class="form-control" name="NOMBRE" required />
              </label>
            </div>
            <div class="col-sm-6">
              <label class="w-100">
                Teléfono:
                <input type="text" class="form-control" name="TELEFONO" />
              </label>
            </div>
            <div class="col-sm-6">
              <label class="w-100">
                Correo:
                <input type="email" class="form-control" name="CORREO" data-mask-email />
              </label>
            </div>
            <div class="col-sm-6">
              <label class="w-100">
                Estado:
                <select class="form-select" name="ESTADO">
                  <option value="ACT">Activo</option>
                  <option value="INC">Inactivo</option>
                </select>
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif ?>
<?php endSection() ?>

<?php section('foot') ?>
<script src="<?= base_url('public/dist/datatables/datatables.min.js') ?>"></script>
<script src="<?= base_url('public/js/form-masks.js') ?>"></script>
<script src="<?= base_url('public/developments/personas/personas.js') ?>"></script>
<?php endSection() ?>

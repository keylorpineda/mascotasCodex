<?php
  $permiso_guardar  = validar_permiso("");
  $permiso_eliminar = validar_permiso("");
  $permiso_editar   = validar_permiso("");
?>

<?php layout('base') ?>

<?php section('titulo') ?>Personas<?php endSection() ?>

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
        <div class="card">
          <div class="card-header text-center" style="background: linear-gradient(to right, #20B2AA, #00FA9A, #20B2AA)">
            <h3 class="card-title mb-0">Gestión de Personas</h3>
          </div>
          <div class="card-body">
            <div class="row mb-3" data-app-filtros>
              <div class="col-sm-4 col-lg-3">
                <label class="w-100">
                  Nombre:
                  <input type="text" class="form-control" placeholder="Nombre..." data-app-filtro-nombre />
                </label>
              </div>
              <div class="col-sm-4 col-lg-3">
                <label class="w-100">
                  Teléfono:
                  <input type="text" class="form-control" placeholder="Teléfono..." data-app-filtro-telefono />
                </label>
              </div>
              <div class="col-sm-4 col-lg-3">
                <label class="w-100">
                  Correo:
                  <input type="text" class="form-control" placeholder="Correo..." data-app-filtro-correo />
                </label>
              </div>
              <div class="col-sm-12 col-lg-3 d-flex align-items-end justify-content-end">
                <button type="button" class="btn btn-primary btn-icon me-2" data-app-filtro-buscar>
                  <i class='bx bx-search-alt-2'></i> Buscar
                </button>
                <?php if ($permiso_guardar): ?>
               <button type="button" class="btn btn-success btn-icon" data-bs-toggle="modal" data-bs-target="#personaCrearModal">
                  <i class="fas fa-plus"></i> Nueva Persona
                </button>
                <?php endif ?>
              </div>
            </div>

            <table id="tpersonas" class="table table-hover w-100">
              <thead class="table-secondary text-muted">
                <tr>
                  <th>Cédula</th>
                  <th>Nombre</th>
                  <th>Teléfono</th>
                  <th>Correo</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>

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
                <input type="email" class="form-control" name="CORREO" />
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
                <input type="email" class="form-control" name="CORREO" />
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
<script src="<?= base_url('public/js/personas.js') ?>"></script>
<?php endSection() ?>

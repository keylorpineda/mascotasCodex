<?php
  $permiso_guardar  = validar_permiso("");
  $permiso_eliminar = validar_permiso("");
  $permiso_editar 	= validar_permiso("");
?>

<?php layout("base") ?>

<?php section("titulo") ?>
    Listado Usuarios
<?php endSection() ?>

<?php section("head") ?>
  <link rel="stylesheet" href="<?= base_url("public/dist/datatables/datatables.min.css") ?>" />
	<style type="text/css">
    .dt-search {
      display: none;
    }

		.form-control[readonly] {
		    background-color: #fff;
		}
	</style>
<?php endSection() ?>

<?php section("bc") ?>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= base_url("inicio") ?>">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?= base_url("usuarios") ?>">Usuarios</a></li>
        <li class="breadcrumb-item active" aria-current="page">Listado</li>
    </ol>
<?php endSection() ?>

<?php section("body") ?>
	<input type="hidden" id="PERMISO_GUARDAR" value="<?= $permiso_guardar ?>">
	<input type="hidden" id="PERMISO_EDITAR" value="<?= $permiso_editar ?>">
	<input type="hidden" id="PERMISO_ELIMINAR" value="<?= $permiso_eliminar ?>">
	<main>
		<div class="container-fluid">
			<div class="row mt-4">
				<div class="col-12">
					<div class="card">
						<div class="card-header text-center" style="background: linear-gradient(to right, #20B2AA, #00FA9A, #20B2AA)">
							<h3 class="card-title">Mantenimiento de Usuarios</h3>
						</div>
						<div class="card-body">
							<div class="row" data-app-filtros>
								<div class="col-sm-4 col-lg-3">
									<label class="w-100">
										Nombre:
										<input type="text" class="form-control" placeholder="Nombre del usuario..." data-app-filtro-nombre />
									</label>
								</div>
								<div class="col-sm-3 col-lg-2">
									<label class="w-100">
										Usuario:
										<input type="text" class="form-control" placeholder="Usuario..." data-app-filtro-usuario />
									</label>
								</div>
								<div class="col-sm-3 col-lg-2">
									<label class="w-100">
										Tipo:
										<select class="form-select" data-app-filtro-idtipo>
											<option value="">Todos</option>
											<?php foreach ($TIPOS_USUARIOS as $TIPO_USUARIO): ?>
												<option value="<?= $TIPO_USUARIO->getIDTIPOUSUARIO() ?>"><?= $TIPO_USUARIO->getNOMBRE() ?></option>
											<?php endforeach ?>
										</select>
									</label>
								</div>
								<div class="col-sm-2 col-lg-2">
									<label class="w-100">
										Estado:
										<select class="form-select" data-app-filtro-estado>
											<option value="">Todos</option>
											<option value="ACT">Activo</option>
											<option value="INC">Inactivo</option>
										</select>
									</label>
								</div>
								<div class="col-sm-12 col-lg-3">
									<br>
									<button type="button" class="btn btn-primary mb-3 btn-icon" data-app-filtro-buscar>
										<i class='bx bx-search-alt-2'></i>
										Buscar
									</button>
									<?php if ($permiso_guardar): ?>
										<button type="button" class="btn btn-success mb-3 btn-icon" data-bs-toggle="modal" data-bs-target="#nuevosUsuariosModal">
											<i class="fas fa-plus pl-2 pr-2"></i>
											Nuevo Usuario
										</button>
									<?php endif ?>
								</div>
							</div>
							<table id="tusuarios" class="table table-hover">
								<thead>
									<tr class="table-secondary text-muted">
										<th></th>
										<th>NOMBRE</th>
										<th>USUARIO</th>
										<th>TIPO</th>
										<th>ESTADO</th>
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
		<div class="modal fade" id="nuevosUsuariosModal" tabindex="-1" aria-labelledby="nuevosUsuariosModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header" style="background-color: #d2f4ea;">
						<h5 class="modal-title" id="nuevosUsuariosModalLabel">Generar / Actualizar Usuarios</h5>
						<button type="button" class="close bg-red" data-bs-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<form id="FORMULARIO-NUEVOS-USUARIOS" autocomplete="off">
						<div class="modal-body">
							<div class="row">
								<div class="col-sm-8">
									<label class="w-100">
										Nombre: <span class="text-danger">*</span>
										<input type="text" class="form-control" name="NOMBRE" placeholder="Nombre Completo..." required />
									</label>
								</div>
								<div class="col-sm-4">
									<label class="w-100">
										Usuario: <span class="text-danger">*</span>
										<input type="text" class="form-control" name="USUARIO" placeholder="Nombre de Usuario..." required />
									</label>
								</div>
								<div class="col-sm-4">
									<label class="w-100">
										Contraseña: <span class="text-danger">*</span>
										<input type="password" class="form-control" name="CONTRASENNA" placeholder="*************" required />
									</label>
								</div>
								<div class="col-sm-4">
									<label class="w-100">
										Repite Contraseña: <span class="text-danger">*</span>
										<input type="password" class="form-control" name="RECONTRASENNA" placeholder="*************" required />
									</label>
								</div>
								<div class="col-sm-4">
									<label class="w-100">
										Role: <span class="text-danger">*</span>
										<select class="form-select" name="ID_TIPO" required>
											<option value="">Seleccione</option>
											<?php foreach ($TIPOS_USUARIOS as $TIPO_USUARIO): ?>
												<option value="<?= $TIPO_USUARIO->getIDTIPOUSUARIO() ?>"><?= $TIPO_USUARIO->getNOMBRE() ?></option>
											<?php endforeach ?>
										</select>
									</label>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="submit" class="btn btn-success btn-icon">
								<i class="fas fa-floppy-disk"></i>
								Guardar
							</button>
							<button type="button" class="btn btn-secondary btn-icon" data-bs-dismiss="modal">
								<i class="fas fa-xmark"></i>
								Cancelar
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	<?php endif ?>
	<?php if ($permiso_editar): ?>
		<div class="modal fade" id="actualizarUsuariosModal" tabindex="-1" aria-labelledby="actualizarUsuariosModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header" style="background-color: #d2f4ea;">
						<h5 class="modal-title" id="actualizarUsuariosModalLabel">Generar / Actualizar Usuarios</h5>
						<button type="button" class="close bg-red" data-bs-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<form id="FORMULARIO-ACTUALIZAR-USUARIOS" autocomplete="off">
						<input type="hidden" name="ID_USUARIO" />
						<input type="hidden" name="_method" value="PUT" />
						<div class="modal-body">
							<div class="row">
								<div class="col-sm-8">
									<label class="w-100">
										Nombre: <span class="text-danger">*</span>
										<input type="text" class="form-control" name="NOMBRE" placeholder="Nombre Completo..." required />
									</label>
								</div>
								<div class="col-sm-4">
									<label class="w-100">
										Usuario: <span class="text-danger">*</span>
										<input type="text" class="form-control" name="USUARIO" placeholder="Nombre de Usuario..." required />
									</label>
								</div>
								<div class="col-sm-6">
									<label class="w-100">
										Contraseña:
										<input type="password" class="form-control" name="CONTRASENNA" placeholder="*************" />
									</label>
								</div>
								<div class="col-sm-6">
									<label class="w-100">
										Role: <span class="text-danger">*</span>
										<select class="form-select" name="ID_TIPO" required>
											<option value="">Seleccione</option>
											<?php foreach ($TIPOS_USUARIOS as $TIPO_USUARIO): ?>
												<option value="<?= $TIPO_USUARIO->getIDTIPOUSUARIO() ?>"><?= $TIPO_USUARIO->getNOMBRE() ?></option>
											<?php endforeach ?>
										</select>
									</label>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="submit" class="btn btn-success btn-icon">
								<i class="fas fa-floppy-disk"></i>
								Guardar
							</button>
							<button type="button" class="btn btn-secondary btn-icon" data-bs-dismiss="modal">
								<i class="fas fa-xmark"></i>
								Cancelar
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	<?php endif ?>
<?php endSection() ?>

<?php section("foot") ?>
  <script src="<?= base_url('public/dist/datatables/datatables.min.js') ?>"></script>
	<script src="<?= base_url("public/developments/usuarios/usuarios.js") ?>?v=1"></script>
<?php endSection() ?>
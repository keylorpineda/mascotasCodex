<?php layout("base") ?>

<?php section("titulo") ?>
    Permisos Usuarios
<?php endSection() ?>

<?php section("bc") ?>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= base_url("inicio") ?>">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?= base_url("usuarios") ?>">Usuarios</a></li>
        <li class="breadcrumb-item active" aria-current="page">Permisos</li>
    </ol>
<?php endSection() ?>

<?php section("head") ?>
<?php endSection() ?>

<?php section("body") ?>
	<main>
	  <div class="container-fluid">
      <div class="card mt-3">
        <div class="card-header text-center" style="background: linear-gradient(to right, #20B2AA, #00FA9A, #20B2AA)">
        	<h3 class="card-title">Permisos de Usuario</h3>
        </div>
        <div class="card-body">
        	<div class="row">
        		<div class="col-sm-4">
        			<div class="card">
        				<div class="card-head fs-2 p-2" style="background-color: #cff4fc">
        					Usuario
        				</div>
        				<div class="card-body">
        					<div class="row">
        						<div class="col-sm-12">
        							<label class="w-100">
        								Usuario
        								<select class="form-select" id="ID_USUARIO">
        									<option value="">Seleccione...</option>
        									<?php foreach ($USUARIOS as $TIPO_USUARIO => $LISTA): ?>
        										<optgroup label="<?= $TIPO_USUARIO ?>">
        											<?php foreach ($LISTA as $USUARIO): ?>
        												<option value="<?= $USUARIO["ID_USUARIO"] ?>"><?= $USUARIO["NOMBRE"] ?></option>
        											<?php endforeach ?>
        										</optgroup>
        									<?php endforeach ?>
        								</select>
        							</label>
        						</div>
        					</div>
        				</div>
        			</div>
        		</div>
        		<div class="col-sm-8">
        			<div class="card">
        				<div class="card-head fs-2 p-2" style="background-color: #cff4fc">
        					Permisos
        				</div>
        				<div class="card-body p-0" style="max-height: 40rem; overflow-y: auto;">
        					<table class="w-100 table table-bordered table-sm" data-app-control-permisos>
        						<tbody>
        							<!-- USUARIOS -->
	        							<tr class="table-secondary">
	        								<td colspan="2" class="p-2"><h4>Usuarios</h4></td>
	        							</tr>
	        							<?php if (validar_permiso("SPADM")): ?>
	        								<tr>
	        									<td class="text-center"><input type="checkbox" class="cursor-pointer" style="transform: scale(2);" name="permisos[]" value="SPADM" id="SPADM"></td>
	        									<td><label for="SPADM" class="w-100 cursor-pointer">Super Administrador</label></td>
	        								</tr>
	        							<?php endif ?>
	        							<tr>
	        								<td class="text-center"><input type="checkbox" class="cursor-pointer" style="transform: scale(2);" name="permisos[]" value="P0001" id="P0001"></td>
	        								<td><label for="P0001" class="w-100 cursor-pointer">Guardar Permisos</label></td>
	        							</tr>
	        							<tr>
	        								<td class="text-center"><input type="checkbox" class="cursor-pointer" style="transform: scale(2);" name="permisos[]" value="U0001" id="U0001"></td>
	        								<td><label for="U0001" class="w-100 cursor-pointer">Guardar Usuarios</label></td>
	        							</tr>
	        							<tr>
	        								<td class="text-center"><input type="checkbox" class="cursor-pointer" style="transform: scale(2);" name="permisos[]" value="U0003" id="U0003"></td>
	        								<td><label for="U0003" class="w-100 cursor-pointer">Inactivar Usuarios</label></td>
	        							</tr>
	        							<tr>
	        								<td class="text-center"><input type="checkbox" class="cursor-pointer" style="transform: scale(2);" name="permisos[]" value="U0002" id="U0002"></td>
	        								<td><label for="U0002" class="w-100 cursor-pointer">Actualizar Usuarios</label></td>
        								</tr>
        						</tbody>
        					</table>
        				</div>
        			</div>
        		</div>
        	</div>
        </div>
      </div>
	  </div>
	</main>
<?php endSection() ?>

<?php section("foot") ?>
	<script type="text/javascript" src="<?= base_url("public/developments/usuarios/permisos.js") ?>?v=1"></script>
<?php endSection() ?>
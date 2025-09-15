<?php
namespace App\Controllers\Usuarios;

use App\Controllers\BaseController;

use App\Entities\Usuarios\PermisosUsuariosEntity;

class Permisos extends BaseController
{
	public function listado()
	{
		// $UsuariosModel = model("Usuarios\UsuariosModel")
		// 	->select(
		// 		"tusuarios.ID_USUARIO",
		// 		"tusuarios.NOMBRE",
		// 		"tusuarios.USUARIO",
		// 		"ttiposusuarios.NOMBRE AS TIPO_USUARIO"
		// 	)
		// 	->inner_join("ttiposusuarios", "ttiposusuarios.ID_TIPO_USUARIO", "tusuarios.ID_TIPO")
		// 	->where(["estado" => "ACT"])
		// 	->orderBy("nombre")
		// 	->toArray();

		// $USUARIOS = array_reduce($UsuariosModel->getAllRows(), function ($carry, $usuario) {
		// 	$carry[$usuario["TIPO_USUARIO"]][] = $usuario;
		// 	return $carry;
		// }, []);
		return view("usuarios/permisos", [
			"USUARIOS" => [],
		]);
	}

	public function obtener()
	{
		$lista_permisos = [];
		if (validar_permiso("P0001")) {
			$ID_USUARIO = trim($_GET['ID_USUARIO']);
			if (empty($ID_USUARIO)) { return json_encode([]); }
			$permisos = model("Usuarios\PermisosUsuariosModel")->where("ID_USUARIO", $ID_USUARIO)->getAllRows();
			$lista_permisos = array_map(fn ($permiso) => $permiso->getPERMISO(), $permisos);
		}
		return json_encode($lista_permisos);
	}

	public function guardar()
	{
		$ALERTA = Danger("No posees permisos para realizar esa acción");
		if (validar_permiso("P0001")) {
			if (!empty($_POST["ID_USUARIO"])) {
				$ALERTA = Success("Registro realizado correctamente");
				$PermisosUsuariosModel = model("Usuarios\PermisosUsuariosModel");
				$PermisosUsuariosEntity = (new PermisosUsuariosEntity())->setIDUSUARIO($_POST["ID_USUARIO"])->setPERMISO($_POST["PERMISO"]);
				if ($_POST["CHECKED"] === "true") {
					if (empty($PermisosUsuariosModel->insert($PermisosUsuariosEntity))) {
						$ALERTA = Warning("No se ha podido realizar el registro");
					}
				} else {
					$ALERTA = Success("Registro removido correctamente");
					if (empty($PermisosUsuariosModel->delete(["PERMISO" => $PermisosUsuariosEntity->getPERMISO()]))) {
						$ALERTA = Warning("No se ha podido remover el registro");
					}
				}
			} else {
				$ALERTA = Warning("No se ha encontrado un usuario al cual asignar el permiso");
			}
		}
		return json_encode($ALERTA->toArray());
	}
}
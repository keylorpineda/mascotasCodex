<?php
namespace App\Controllers\Usuarios;

use App\Controllers\BaseController;

use App\Entities\Usuarios\UsuariosEntity;

class Usuarios extends BaseController
{
	public function listado()
	{
		return view("usuarios/usuarios", [
			"TIPOS_USUARIOS" => [],
		]);
	}
 
	public function menu()
	{
		return view("usuarios/menu");
	}
 
	public function guardar()
	{
		if (validar_permiso(["U0001"])) {
			if ($_POST['RECONTRASENNA'] !== $_POST['CONTRASENNA']) { return Warning("Las contraseñas no coinciden")->toArray(); }
			$UsuariosEntity = new UsuariosEntity([
				"NOMBRE" 	  => $_POST["NOMBRE"],
				"USUARIO" 	  => $_POST["USUARIO"],
				"CONTRASENNA" => password_hash($_POST["CONTRASENNA"], PASSWORD_DEFAULT),
				"ID_TIPO" 	  => $_POST["ID_TIPO"],
				"ESTADO"	  => "ACT",
			]);
			$resp = model("Usuarios\UsuariosModel")->insert($UsuariosEntity);
			if (!empty($resp)) {
				return json_encode(Success("Registro realizado correctamente")->toArray());
			}
			return json_encode(Warning("No ha sido posible realizar el registro, intentalo de nuevo más tarde")->toArray());
		}
		return json_encode(Danger("No posees permisos para realizar esa acción")->toArray());
	}

	public function editar()
	{
		is_logged_in();
		if (validar_permiso(["U0002"])) {
			if (empty(trim($_POST["CONTRASENNA"]))) { unset($_POST["CONTRASENNA"]); }
			$UsuariosEntity = new UsuariosEntity([
				"NOMBRE" 	  => $_POST["NOMBRE"],
				"USUARIO" 	  => $_POST["USUARIO"],
				"ID_TIPO" 	  => $_POST["ID_TIPO"],
				"ESTADO"	  => "ACT",
			]);
			if (isset($_POST["CONTRASENNA"])) { $UsuariosEntity->setCONTRASENNA(password_hash($_POST["CONTRASENNA"], PASSWORD_DEFAULT)); }
			$resp = model("Usuarios\UsuariosModel")->update($UsuariosEntity, (int)($_POST["ID_USUARIO"]));
			if (!empty($resp)) {
				return Success("Registro realizado correctamente")->toArray();
			}
			return json_encode(Warning("No han habido cambios en el registro")->toArray());
		}
		return json_encode(Danger("No posees permisos para realizar esa acción")->toArray());
	}

	public function remover()
	{
		is_logged_in();
		if (validar_permiso(["U0003"])) {
			$ID_USUARIO = $_POST['idusuario'];
			$resp = model("Usuarios\UsuariosModel")->update(["ESTADO" => "INC"], $ID_USUARIO);
			if (!empty($resp)) {
				return json_encode(Success("Registro inactivado correctamente")->toArray());
			}
			return json_encode(Warning("No ha sido posible inactivar el registro, intentalo de nuevo más tarde")->toArray());
		}
		return json_encode(Danger("No posees permisos para realizar esa acción")->toArray());
	}

	public function obtener()
	{
		is_logged_in();
		$USUARIOS = [];
		if (validar_permiso(["U0001", "U0002", "U0003"])) {
			$NOMBRE = trim($_GET['nombre'] ?? "" ?:"");
			$ID_TIPO = trim($_GET['idtipo'] ?? "" ?:"");
			$ESTADO = trim($_GET['estado'] ?? "" ?:"");
			$USUARIO = trim($_GET['usuario'] ?? "" ?:"");
			$ID_USUARIO = trim($_GET['idusuario'] ?? "" ?:"");

			$UsuariosModel = (model("Usuarios\UsuariosModel"));
			if (!empty($ID_USUARIO)) {
				$USUARIO = $UsuariosModel
					->select("ID_USUARIO", "NOMBRE", "USUARIO", "ID_TIPO")
					->where("ID_USUARIO", $ID_USUARIO)
					->toArray()
					->getFirstRow();
				return json_encode($USUARIO);
			}

			$where_list = ["tu.ESTADO LIKE ?"];
			$params = ["%{$ESTADO}%"];
			if (!empty($NOMBRE)) {
				$NOMBRE = explode(" ", $NOMBRE);
				$nombres_list = [];
				foreach ($NOMBRE as $key => $value) {
					if (empty(trim($value))) { continue; }
					$nombres_list[] = "tu.NOMBRE LIKE ?";
					$params[] = "%{$value}%";
				}
				$where_list[] = "(".implode(" OR ", $nombres_list).")";
			}
			if (!empty($ID_TIPO)) {
				$where_list[] = "tu.ID_TIPO = ?";
				$params[] = $ID_TIPO;
			}
			if (!empty($USUARIO)) {
				$where_list[] = "tu.USUARIO LIKE ?";
				$params[] = "%{$USUARIO}%";
			}
			$where = implode(" AND ", $where_list);

			$USUARIOS = $UsuariosModel->query(
				"SELECT tu.ID_USUARIO, tu.NOMBRE, tu.USUARIO, utu.NOMBRE AS TIPO_USUARIO, tu.ESTADO FROM tusuarios AS tu JOIN ttiposusuarios AS utu ON utu.ID_TIPO_USUARIO = tu.ID_TIPO WHERE {$where}",
				$params
			);
		}
		return json_encode([ "data" => $USUARIOS ]);
	}

	public function login()
	{
		$CsrfService = service("CsrfService")->setLifetime(1200);
		$CsrfService->regenerar_token();
		$TOKEN_CSRF  = $CsrfService->obtener_token();

		session()->delete("is_not_logged_in");
		$ALERTA = session("ALERTA");
		return view("login/login", [
			"ALERTA" => $ALERTA,
			"TOKEN_CSRF" => $TOKEN_CSRF,
		]);
	}

	public function validar()
	{
		$CSRF_TOKEN    = $_POST['CSRF_TOKEN'];
		$INPUT_IGNORAR = $_POST['IGNORAR'];

		$TOKEN_CSRF_VALIDO = service("CsrfService")->setLifetime(1200)->verificar_token($CSRF_TOKEN);

		if (empty($INPUT_IGNORAR) && $TOKEN_CSRF_VALIDO) {
			$password = trim($_POST['password']);
			$user  	  = trim($_POST['user']);
			if (!empty($user) && !empty($password)) {
				$USUARIO = (model("Usuarios\UsuariosModel"))->select(
					"CONTRASENNA",
					"ID_TIPO",
					"ID_USUARIO",
				)->where([
					"USUARIO" => $user,
					"ESTADO"  => "ACT",
				])->getFirstRow();
				if ($USUARIO && false !== $USUARIO->validar_contrasenna($password)) {
					set_cookie(COOKIE_ID_USUARIO, 	   $USUARIO->getIDUSUARIO());
					set_cookie(COOKIE_ID_TIPO_USUARIO, $USUARIO->getIDTIPO());
					return redirect(base_url("inicio"), [
						"ALERTA" => Success("Hola, bienvenido ;)"),
					]);
				}
			}
		}

		return redirect(base_url("login"), [
			"ALERTA" => Warning("Usuario y/o contraseña incorrectos."),
		]);
	}

	public function logout()
	{
		session()->delete("__USUARIO__");
		delete_cookie(COOKIE_ID_USUARIO);
		delete_cookie(COOKIE_ID_TIPO_USUARIO);
		return redirect(base_url("login"), [
			"ALERTA" => Info("Nos vemos, vuelve pronto!"),
		]);
	}
}
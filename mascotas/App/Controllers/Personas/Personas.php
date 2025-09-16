<?php
namespace App\Controllers\Personas;

use App\Controllers\BaseController;

class Personas extends BaseController
{
    public function listado()
    {
        return view('personas/personas', []);
    }

    public function obtener()
{
    is_logged_in();

    $data = [];
    if (validar_permiso(['PE0001','PE0002','PE0003'])) {
        $NOMBRE     = trim($_GET['nombre']     ?? '');
        $TELEFONO   = trim($_GET['telefono']   ?? '');
        $CORREO     = trim($_GET['correo']     ?? '');
        $ID_PERSONA = trim($_GET['idpersona']  ?? '');

        $PersonasModel = model('Personas\\PersonasModel');

        if ($ID_PERSONA !== '') {
            $row = $PersonasModel
                ->select('ID_PERSONA','NOMBRE','TELEFONO','CORREO')
                ->where('ID_PERSONA', $ID_PERSONA)
                ->toArray()
                ->getFirstRow();
            return json_encode($row ?? []);
        }

        $where_list = ['1=1'];
        $params     = [];

        if ($NOMBRE !== '') {
            $parts = array_filter(explode(' ', $NOMBRE), fn($v)=>trim($v)!=='');
            if (!empty($parts)) {
                $like=[]; foreach($parts as $p){ $like[]='NOMBRE LIKE ?'; $params[]="%{$p}%"; }
                $where_list[] = '(' . implode(' OR ', $like) . ')';
            }
        }
        if ($TELEFONO !== '') { $where_list[]='TELEFONO LIKE ?'; $params[]="%{$TELEFONO}%"; }
        if ($CORREO   !== '') { $where_list[]='CORREO LIKE ?';   $params[]="%{$CORREO}%"; }

        $where  = implode(' AND ', $where_list);
        $result = $PersonasModel->query(
            "SELECT ID_PERSONA, NOMBRE, TELEFONO, CORREO
               FROM tpersonas
              WHERE {$where}
           ORDER BY NOMBRE ASC",
            $params
        );
        $data = $result->getResultArray();
    }

    return json_encode(['data'=>$data]);
}



    public function buscar_por_cedula()
    {
        is_logged_in();
        $ced = trim($_GET['cedula'] ?? '');
        if ($ced === '') return json_encode([]);

        $row = model('Personas\\PersonasModel')
            ->select('ID_PERSONA','NOMBRE','TELEFONO','CORREO')
            ->where('ID_PERSONA', $ced)
            ->toArray()
            ->getFirstRow();

        return json_encode($row ?? []);
    }

    public function guardar()
    {
        is_logged_in();

        if (!validar_permiso(['PE0001'])) {
            return json_encode(Danger('No posees permisos para realizar esa acción')->toArray());
        }

        $ID_PERSONA = trim($_POST['ID_PERSONA'] ?? '');
        $NOMBRE     = trim($_POST['NOMBRE']     ?? '');
        $TELEFONO   = trim($_POST['TELEFONO']   ?? '');
        $CORREO     = trim($_POST['CORREO']     ?? '');

        if ($ID_PERSONA==='' || $NOMBRE==='') {
            return json_encode(Warning('Cédula y Nombre son obligatorios')->toArray());
        }

        $existe = model('Personas\\PersonasModel')
           ->select('ID_PERSONA')
            ->where('ID_PERSONA',$ID_PERSONA)->limit(1)->get();
        if (!empty($existe)) {
            return json_encode(Warning('Ya existe una persona con esa cédula')->toArray());
        }

        $resp = model('Personas\\PersonasModel')->insert([
            'ID_PERSONA' => $ID_PERSONA,
            'NOMBRE'     => $NOMBRE,
            'TELEFONO'   => $TELEFONO ?: null,
            'CORREO'     => $CORREO   ?: null,
        ]);

        if (!empty($resp)) return json_encode(Success('Persona creada correctamente')->toArray());
        return json_encode(Warning('No ha sido posible crear la persona, intentalo de nuevo más tarde')->toArray());
    }

    public function editar()
    {
        is_logged_in();

        if (!validar_permiso(['PE0002'])) {
            return json_encode(Danger('No posees permisos para realizar esa acción')->toArray());
        }

        $ID_PERSONA = trim($_POST['ID_PERSONA'] ?? '');
        $NOMBRE     = trim($_POST['NOMBRE']     ?? '');
        $TELEFONO   = trim($_POST['TELEFONO']   ?? '');
        $CORREO     = trim($_POST['CORREO']     ?? '');

        if ($ID_PERSONA==='' || $NOMBRE==='') {
            return json_encode(Warning('Campos incompletos')->toArray());
        }

        $resp = model('Personas\\PersonasModel')->update([
            'ID_PERSONA' => $ID_PERSONA,
            'NOMBRE'     => $NOMBRE,
            'TELEFONO'   => $TELEFONO ?: null,
            'CORREO'     => $CORREO   ?: null,
        ]);

        if (!empty($resp)) return json_encode(Success('Persona actualizada correctamente')->toArray());
        return json_encode(Warning('No han habido cambios en el registro')->toArray());
    }

    public function remover()
    {
        is_logged_in();

        if (!validar_permiso(['PE0003'])) {
            return json_encode(Danger('No posees permisos para realizar esa acción')->toArray());
        }

        $ID_PERSONA = trim($_POST['idpersona'] ?? '');
        if ($ID_PERSONA==='') {
            return json_encode(Warning('Solicitud inválida')->toArray());
        }

        $dbUsuarios = model('Usuarios\\UsuariosModel')
            ->select('ID_USUARIO')
            ->where('ID_PERSONA', $ID_PERSONA)->limit(1)->get();
        if (!empty($dbUsuarios)) {
            return json_encode(Warning('No es posible eliminar: la persona tiene un usuario asociado')->toArray());
        }

        $dbMascotas = model('Mascotas\\MascotasModel')
            ->select('ID_MASCOTA')
            ->where('ID_PERSONA', $ID_PERSONA)->limit(1)->get();
        if (!empty($dbMascotas)) {
            return json_encode(Warning('No es posible eliminar: la persona tiene mascotas asociadas')->toArray());
        }

        $ok = model('Personas\\PersonasModel')->delete($ID_PERSONA);
        if (!empty($ok)) return json_encode(Success('Persona eliminada correctamente')->toArray());
        return json_encode(Warning('No ha sido posible eliminar el registro')->toArray());
    }
}

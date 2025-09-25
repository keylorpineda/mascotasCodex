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

        if (!validar_permiso(['PE0001', 'PE0002', 'PE0003'])) {
            return json_encode(Danger('No posees permisos para realizar esa acción')->setPROCESS('personas.obtener')->toArray());
        }

        $NOMBRE     = trim($_GET['nombre']     ?? '');
        $TELEFONO   = trim($_GET['telefono']   ?? '');
        $CORREO     = trim($_GET['correo']     ?? '');
        $ESTADO     = trim($_GET['estado']     ?? 'ACT');
        $ID_PERSONA = trim($_GET['idpersona']  ?? '');

        $PersonasModel = model('Personas\\PersonasModel');

        if ($ID_PERSONA !== '') {
            $row = $PersonasModel
                ->select('ID_PERSONA', 'NOMBRE', 'TELEFONO', 'CORREO', 'ESTADO')
                ->where('ID_PERSONA', $ID_PERSONA)
                ->toArray()
                ->getFirstRow();

            if ($row === null) {
                return json_encode(Warning('No se encontraron registros', 'Sin resultados')->setSTATUS(false)->setPROCESS('personas.obtener')->setDATA([])->toArray());
            }

            return json_encode(Success('Persona encontrada correctamente', 'Consulta exitosa')->setPROCESS('personas.obtener')->setDATA($row)->toArray());
        }

        $where_list = ['1=1'];
        $params     = [];

        if ($ESTADO !== '') {
            $where_list[] = 'ESTADO = ?';
            $params[] = $ESTADO;
        }
        if ($NOMBRE !== '') {
            $parts = array_filter(explode(' ', $NOMBRE), fn($v) => trim($v) !== '');
            if (!empty($parts)) {
                $like = [];
                foreach ($parts as $p) {
                    $like[] = 'NOMBRE LIKE ?';
                    $params[] = "%{$p}%";
                }
                $where_list[] = '(' . implode(' OR ', $like) . ')';
            }
        }
        if ($TELEFONO !== '') {
            $where_list[] = 'TELEFONO LIKE ?';
            $params[] = "%{$TELEFONO}%";
        }
        if ($CORREO   !== '') {
            $where_list[] = 'CORREO LIKE ?';
            $params[] = "%{$CORREO}%";
        }

        $where  = implode(' AND ', $where_list);
        $data = $PersonasModel->query(
            "SELECT ID_PERSONA, NOMBRE, TELEFONO, CORREO, ESTADO
               FROM tpersonas
              WHERE {$where}
           ORDER BY NOMBRE ASC",
            $params
        );
        if (is_object($data) && method_exists($data, 'getResultArray')) {
            $data = $data->getResultArray();
        }

        return json_encode(
            Success('Listado de personas consultado correctamente', 'Consulta exitosa')
                ->setPROCESS('personas.obtener')
                ->setDATA($data)
                ->toArray()
        );
    }



    public function buscar_por_cedula()
    {
        is_logged_in();
        $ced = trim($_GET['cedula'] ?? '');
        if ($ced === '') {
            return json_encode(Warning('Debe indicar la cédula a consultar', 'Solicitud incompleta')->setDATA([])->setPROCESS('personas.buscar_por_cedula')->toArray());
        }

        $row = model('Personas\\PersonasModel')
            ->select('ID_PERSONA', 'NOMBRE', 'TELEFONO', 'CORREO', 'ESTADO')
            ->where('ID_PERSONA', $ced)
            ->toArray()
            ->getFirstRow();

        if ($row === null) {
            return json_encode(Warning('No se localizaron datos para la cédula consultada', 'Sin resultados')->setDATA([])->setPROCESS('personas.buscar_por_cedula')->toArray());
        }

        return json_encode(Success('Persona encontrada correctamente', 'Consulta exitosa')->setPROCESS('personas.buscar_por_cedula')->setDATA($row)->toArray());
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

        if ($ID_PERSONA === '' || $NOMBRE === '') {
            return json_encode(Warning('Cédula y Nombre son obligatorios')->setPROCESS('personas.guardar')->toArray());
        }

        $existe = model('Personas\\PersonasModel')
            ->select('ID_PERSONA')
            ->where('ID_PERSONA', $ID_PERSONA)
            ->limit(1)
            ->toArray()
            ->getFirstRow();
        if ($existe !== null) {
            return json_encode(Warning('Ya existe una persona con esa cédula')->setPROCESS('personas.guardar')->toArray());
        }

        $PersonasModel = model('Personas\\PersonasModel');

        try {
            $resp = $PersonasModel->insert([
                'ID_PERSONA' => $ID_PERSONA,
                'NOMBRE'     => $NOMBRE,
                'TELEFONO'   => $TELEFONO ?: null,
                'CORREO'     => $CORREO   ?: null,
                'ESTADO'     => 'ACT',
            ]);
        } catch (\Throwable $th) {
            log_message('error', 'Error al insertar persona: {error}', ['error' => $th->getMessage()]);
            return json_encode(Danger('No fue posible crear la persona: ' . $th->getMessage())->setPROCESS('personas.guardar')->toArray());
        }

        if ($resp !== null && $resp !== false) {
            return json_encode(Success('Persona creada correctamente')->setPROCESS('personas.guardar')->toArray());
        }
        return json_encode(Warning('No ha sido posible crear la persona, intentalo de nuevo más tarde')->setPROCESS('personas.guardar')->toArray());
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

        if ($ID_PERSONA === '' || $NOMBRE === '') {
            return json_encode(Warning('Campos incompletos')->setPROCESS('personas.editar')->toArray());
        }

        $dataUpdate = [
            'ID_PERSONA' => $ID_PERSONA,
            'NOMBRE'     => $NOMBRE,
            'TELEFONO'   => $TELEFONO ?: null,
            'CORREO'     => $CORREO   ?: null,
        ];

        if (isset($_POST['ESTADO']) && trim($_POST['ESTADO']) !== '') {
            $dataUpdate['ESTADO'] = trim($_POST['ESTADO']);
        }

        $resp = model('Personas\\PersonasModel')->update($dataUpdate);

        if (!empty($resp)) {
            return json_encode(Success('Persona actualizada correctamente')->setPROCESS('personas.editar')->toArray());
        }
        return json_encode(Warning('No han habido cambios en el registro')->setPROCESS('personas.editar')->toArray());
    }

    public function remover()
    {
        is_logged_in();

        if (!validar_permiso(['PE0003'])) {
            return json_encode(Danger('No posees permisos para realizar esa acción')->toArray());
        }

        $ID_PERSONA = trim($_POST['idpersona'] ?? '');
        if ($ID_PERSONA === '') {
            return json_encode(Warning('Solicitud inválida')->setPROCESS('personas.remover')->toArray());
        }

        $PersonasModel = model('Personas\\PersonasModel');

        $persona = $PersonasModel
            ->select('ID_PERSONA', 'ESTADO')
            ->where('ID_PERSONA', $ID_PERSONA)
            ->limit(1)
            ->toArray()
            ->getFirstRow();

        if ($persona === null) {
            return json_encode(Warning('No se encontró la persona solicitada')->setPROCESS('personas.remover')->toArray());
        }

        if (($persona['ESTADO'] ?? '') === 'INC') {
            return json_encode(Warning('La persona ya se encuentra inactiva')->setPROCESS('personas.remover')->toArray());
        }

        $ok = $PersonasModel->update(['ESTADO' => 'INC'], $ID_PERSONA);
        if (!empty($ok)) {
            return json_encode(Success('Persona inactivada correctamente')->setPROCESS('personas.remover')->toArray());
        }
        return json_encode(Warning('No ha sido posible inactivar el registro')->setPROCESS('personas.remover')->toArray());
    }
}

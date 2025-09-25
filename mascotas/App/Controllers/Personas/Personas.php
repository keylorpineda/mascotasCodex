<?php

namespace App\Controllers\Personas;

use App\Controllers\BaseController;
use Config\Database;

class Personas extends BaseController
{
    private function normalizarCedula(?string $valor): string
    {
        return preg_replace('/\D+/', '', $valor ?? '') ?: '';
    }

    private function obtenerPersonaPorCedulaLimpia(string $cedulaLimpia): ?array
    {
        if ($cedulaLimpia === '') {
            return null;
        }

        $resultado = model('Personas\\PersonasModel')->query(
            "SELECT ID_PERSONA, NOMBRE, TELEFONO, CORREO
               FROM tpersonas
              WHERE REPLACE(ID_PERSONA, '-', '') = ?
              LIMIT 1",
            [$cedulaLimpia]
        );

        if (is_object($resultado)) {
            if (method_exists($resultado, 'getFirstRow')) {
                $fila = $resultado->getFirstRow('array');
                if (!empty($fila)) {
                    return $fila;
                }
            }
            if (method_exists($resultado, 'getResultArray')) {
                $filas = $resultado->getResultArray();
                if (!empty($filas)) {
                    return $filas[0];
                }
            }
        }

        if (is_array($resultado) && !empty($resultado)) {
            $primero = reset($resultado);
            if (is_array($primero)) {
                return $primero;
            }
            if (is_object($primero)) {
                return (array) $primero;
            }
        }

        return null;
    }

    public function listado()
    {
        return view('personas/personas', []);
    }

    public function obtener()
    {
        is_logged_in();
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!validar_permiso(['PE0001', 'PE0002', 'PE0003'])) {
                echo json_encode(
                    Danger('No posees permisos para realizar esa acción')
                        ->setPROCESS('personas.obtener')
                        ->toArray()
                );
                exit;
            }

            $NOMBRE     = trim($_GET['nombre']     ?? '');
            $TELEFONO   = trim($_GET['telefono']   ?? '');
            $CORREO     = trim($_GET['correo']     ?? '');
            $ID_PERSONA = $this->normalizarCedula($_GET['idpersona'] ?? '');

            if ($ID_PERSONA !== '' && ($NOMBRE === '' && $TELEFONO === '' && $CORREO === '')) {
                $row = $this->obtenerPersonaPorCedulaLimpia($ID_PERSONA);

                if ($row === null) {
                    echo json_encode(
                        Warning('No se encontraron registros', 'Sin resultados')
                            ->setSTATUS(false)
                            ->setPROCESS('personas.obtener')
                            ->setDATA([])
                            ->toArray()
                    );
                    exit;
                }

                echo json_encode(
                    Success('Persona encontrada correctamente', 'Consulta exitosa')
                        ->setPROCESS('personas.obtener')
                        ->setDATA($row)
                        ->toArray()
                );
                exit;
            }

            $where_list = ['1=1'];
            $params     = [];

            if ($ID_PERSONA !== '') {
                $where_list[] = "REPLACE(ID_PERSONA, '-', '') = ?";
                $params[]     = $ID_PERSONA;
            }
            if ($NOMBRE !== '') {
                $parts = array_filter(explode(' ', $NOMBRE), static function ($v) {
                    return trim($v) !== '';
                });
                if (!empty($parts)) {
                    $like = [];
                    foreach ($parts as $p) {
                        $like[]  = 'NOMBRE LIKE ?';
                        $params[] = "%{$p}%";
                    }
                    $where_list[] = '(' . implode(' OR ', $like) . ')';
                }
            }
            if ($TELEFONO !== '') {
                $where_list[] = 'TELEFONO LIKE ?';
                $params[]     = "%{$TELEFONO}%";
            }
            if ($CORREO !== '') {
                $where_list[] = 'CORREO LIKE ?';
                $params[]     = "%{$CORREO}%";
            }

            $where = implode(' AND ', $where_list);

            $PersonasModel = model('Personas\\PersonasModel');
            $data = $PersonasModel->query(
                "SELECT ID_PERSONA, NOMBRE, TELEFONO, CORREO
                   FROM tpersonas
                  WHERE {$where}
               ORDER BY NOMBRE ASC",
                $params
            );

            if (is_object($data) && method_exists($data, 'getResultArray')) {
                $data = $data->getResultArray();
            } elseif (!is_array($data)) {
                $data = [];
            }

            echo json_encode(
                Success('Listado de personas consultado correctamente', 'Consulta exitosa')
                    ->setPROCESS('personas.obtener')
                    ->setDATA($data)
                    ->toArray()
            );
            exit;
        } catch (\Throwable $e) {
            log_message('error', 'Error al obtener personas: {error}', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(
                Danger('Error interno: ' . $e->getMessage(), 'Error del servidor')
                    ->setPROCESS('personas.obtener')
                    ->setDATA([])
                    ->setERRORS(['exception' => $e->getMessage()])
                    ->toArray()
            );
            exit;
        }
    }

    public function buscar_por_cedula()
    {
        is_logged_in();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $cedulaLimpia = $this->normalizarCedula($_GET['cedula'] ?? '');
            if ($cedulaLimpia === '') {
                echo json_encode(
                    Warning('Debe indicar la cédula a consultar', 'Solicitud incompleta')
                        ->setDATA([])
                        ->setPROCESS('personas.buscar_por_cedula')
                        ->toArray()
                );
                exit;
            }

            $row = $this->obtenerPersonaPorCedulaLimpia($cedulaLimpia);
            if ($row === null) {
                echo json_encode(
                    Warning('No se localizaron datos para la cédula consultada', 'Sin resultados')
                        ->setDATA([])
                        ->setPROCESS('personas.buscar_por_cedula')
                        ->toArray()
                );
                exit;
            }

            echo json_encode(
                Success('Persona encontrada correctamente', 'Consulta exitosa')
                    ->setPROCESS('personas.buscar_por_cedula')
                    ->setDATA($row)
                    ->toArray()
            );
            exit;
        } catch (\Throwable $e) {
            log_message('error', 'Error al consultar persona por cédula: {error}', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(
                Danger('Error interno: ' . $e->getMessage(), 'Error del servidor')
                    ->setPROCESS('personas.buscar_por_cedula')
                    ->setDATA([])
                    ->setERRORS(['exception' => $e->getMessage()])
                    ->toArray()
            );
            exit;
        }
    }

    public function guardar()
    {
        is_logged_in();
        header('Content-Type: application/json; charset=utf-8');

        if (!validar_permiso(['PE0001'])) {
            echo json_encode(
                Danger('No posees permisos para realizar esa acción')
                    ->setPROCESS('personas.guardar')
                    ->toArray()
            );
            exit;
        }

        $ID_PERSONA = $this->normalizarCedula($_POST['ID_PERSONA'] ?? '');
        $NOMBRE     = trim($_POST['NOMBRE']     ?? '');
        $TELEFONO   = trim($_POST['TELEFONO']   ?? '');
        $CORREO     = trim($_POST['CORREO']     ?? '');

        if ($ID_PERSONA === '' || $NOMBRE === '') {
            echo json_encode(
                Warning('Cédula y Nombre son obligatorios')
                    ->setPROCESS('personas.guardar')
                    ->toArray()
            );
            exit;
        }

        if ($this->obtenerPersonaPorCedulaLimpia($ID_PERSONA) !== null) {
            echo json_encode(
                Warning('Ya existe una persona con esa cédula')
                    ->setPROCESS('personas.guardar')
                    ->toArray()
            );
            exit;
        }

        try {
            $PersonasModel = model('Personas\\PersonasModel');
            $insert = $PersonasModel->insert([
                'ID_PERSONA' => $ID_PERSONA,
                'NOMBRE'     => $NOMBRE,
                'TELEFONO'   => $TELEFONO !== '' ? $TELEFONO : null,
                'CORREO'     => $CORREO   !== '' ? $CORREO   : null,
            ]);

            if ($insert === false) {
                $errores = $PersonasModel->errors();
                $mensaje = !empty($errores) ? implode(' ', $errores) : 'No fue posible crear la persona.';
                throw new \RuntimeException($mensaje);
            }

            echo json_encode(
                Success('Persona creada correctamente')
                    ->setPROCESS('personas.guardar')
                    ->toArray()
            );
            exit;
        } catch (\Throwable $th) {
            log_message('error', 'Error al insertar persona: {error}', ['error' => $th->getMessage()]);
            http_response_code(400);
            echo json_encode(
                Danger('No fue posible crear la persona: ' . $th->getMessage())
                    ->setPROCESS('personas.guardar')
                    ->toArray()
            );
            exit;
        }
    }

    public function editar()
    {
        is_logged_in();
        header('Content-Type: application/json; charset=utf-8');

        if (!validar_permiso(['PE0002'])) {
            echo json_encode(
                Danger('No posees permisos para realizar esa acción')
                    ->setPROCESS('personas.editar')
                    ->toArray()
            );
            exit;
        }

        $ID_PERSONA  = $this->normalizarCedula($_POST['ID_PERSONA'] ?? '');
        $ID_ORIGINAL = $this->normalizarCedula($_POST['ID'] ?? '') ?: $ID_PERSONA;
        $NOMBRE      = trim($_POST['NOMBRE']   ?? '');
        $TELEFONO    = trim($_POST['TELEFONO'] ?? '');
        $CORREO      = trim($_POST['CORREO']   ?? '');

        if ($ID_PERSONA === '' || $NOMBRE === '') {
            echo json_encode(
                Warning('Campos incompletos')
                    ->setPROCESS('personas.editar')
                    ->toArray()
            );
            exit;
        }

        if ($ID_PERSONA !== $ID_ORIGINAL && $this->obtenerPersonaPorCedulaLimpia($ID_PERSONA) !== null) {
            echo json_encode(
                Warning('Ya existe una persona con la nueva cédula ingresada')
                    ->setPROCESS('personas.editar')
                    ->toArray()
            );
            exit;
        }

        $setParts = ['ID_PERSONA = ?', 'NOMBRE = ?', 'TELEFONO = ?', 'CORREO = ?'];
        $valores  = [
            $ID_PERSONA,
            $NOMBRE,
            $TELEFONO !== '' ? $TELEFONO : null,
            $CORREO   !== '' ? $CORREO   : null,
        ];

        $valores[] = $ID_ORIGINAL;

        try {
            $PersonasModel = model('Personas\\PersonasModel');
            $resp = $PersonasModel->query(
                'UPDATE tpersonas SET ' . implode(', ', $setParts) . " WHERE REPLACE(ID_PERSONA, '-', '') = ?",
                $valores
            );

            if ($resp !== false) {
                echo json_encode(
                    Success('Persona actualizada correctamente')
                        ->setPROCESS('personas.editar')
                        ->toArray()
                );
                exit;
            }

            echo json_encode(
                Warning('No han habido cambios en el registro')
                    ->setPROCESS('personas.editar')
                    ->toArray()
            );
            exit;
        } catch (\Throwable $e) {
            log_message('error', 'Error al actualizar persona: {error}', ['error' => $e->getMessage()]);
            http_response_code(400);
            echo json_encode(
                Danger('No fue posible actualizar la persona: ' . $e->getMessage())
                    ->setPROCESS('personas.editar')
                    ->toArray()
            );
            exit;
        }
    }

    public function remover()
    {
        is_logged_in();
        header('Content-Type: application/json; charset=utf-8');

        if (!validar_permiso(['PE0003'])) {
            echo json_encode(
                Danger('No posees permisos para realizar esa acción')
                    ->setPROCESS('personas.remover')
                    ->toArray()
            );
            exit;
        }

        $ID_PERSONA = $this->normalizarCedula($_POST['idpersona'] ?? '');
        if ($ID_PERSONA === '') {
            echo json_encode(
                Warning('Solicitud inválida')
                    ->setPROCESS('personas.remover')
                    ->toArray()
            );
            exit;
        }

        $persona = $this->obtenerPersonaPorCedulaLimpia($ID_PERSONA);
        if ($persona === null) {
            echo json_encode(
                Warning('No se encontró la persona solicitada')
                    ->setPROCESS('personas.remover')
                    ->toArray()
            );
            exit;
        }

        try {
            $PersonasModel = model('Personas\\PersonasModel');
            $db            = Database::connect();
            $resultado     = $db->query(
                "SELECT COUNT(*) AS total FROM tmascotas WHERE REPLACE(ID_PERSONA, '-', '') = ?",
                [$ID_PERSONA]
            );
            $conteo = 0;
            if (is_object($resultado) && method_exists($resultado, 'getFirstRow')) {
                $conteo = (int) ($resultado->getFirstRow('array')['total'] ?? 0);
            }

            if ($conteo > 0) {
                echo json_encode(
                    Warning('No se puede eliminar la persona porque tiene mascotas asociadas')
                        ->setPROCESS('personas.remover')
                        ->toArray()
                );
                exit;
            }

            $resp = $PersonasModel->query(
                "DELETE FROM tpersonas WHERE REPLACE(ID_PERSONA, '-', '') = ?",
                [$ID_PERSONA]
            );

            if ($resp !== false) {
                echo json_encode(
                    Success('Persona eliminada correctamente')
                        ->setPROCESS('personas.remover')
                        ->toArray()
                );
                exit;
            }

            echo json_encode(
                Warning('No ha sido posible eliminar el registro')
                    ->setPROCESS('personas.remover')
                    ->toArray()
            );
            exit;
        } catch (\Throwable $e) {
            log_message('error', 'Error al eliminar persona: {error}', ['error' => $e->getMessage()]);
            http_response_code(400);
            echo json_encode(
                Danger('No ha sido posible eliminar el registro: ' . $e->getMessage())
                    ->setPROCESS('personas.remover')
                    ->toArray()
            );
            exit;
        }
    }
}

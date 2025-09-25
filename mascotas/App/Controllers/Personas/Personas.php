<?php

namespace App\Controllers\Personas;

use App\Controllers\BaseController;

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

        try {
            $pdo = \data_base();
            $stmt = $pdo->prepare(
                "SELECT ID_PERSONA, NOMBRE, TELEFONO, CORREO, ESTADO
                   FROM tpersonas
                  WHERE REPLACE(ID_PERSONA, '-', '') = ?
                  LIMIT 1"
            );
            $stmt->execute([$cedulaLimpia]);
            $fila = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $fila !== false ? $fila : null;
        } catch (\Throwable $e) {
            log_message('error', 'Error al consultar persona por cédula limpia: {error}', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function listado()
    {
        return view('personas/personas', []);
    }

    public function obtener()
    {
        is_logged_in();

        try {
            if (!validar_permiso(['PE0001', 'PE0002', 'PE0003'])) {
                return $this->response->setJSON(
                    Danger('No posees permisos para realizar esa acción')
                        ->setPROCESS('personas.obtener')
                        ->toArray()
                );
            }

            $NOMBRE     = trim($_GET['nombre']     ?? '');
            $TELEFONO   = trim($_GET['telefono']   ?? '');
            $CORREO     = trim($_GET['correo']     ?? '');
            $ESTADO     = trim($_GET['estado']     ?? 'ACT');
            $ID_PERSONA = $this->normalizarCedula($_GET['idpersona'] ?? '');

            if ($ID_PERSONA !== '') {
                $row = $this->obtenerPersonaPorCedulaLimpia($ID_PERSONA);

                if ($row === null) {
                    return $this->response->setJSON(
                        Warning('No se encontraron registros', 'Sin resultados')
                            ->setSTATUS(false)
                            ->setPROCESS('personas.obtener')
                            ->setDATA([])
                            ->toArray()
                    );
                }

                return $this->response->setJSON(
                    Success('Persona encontrada correctamente', 'Consulta exitosa')
                        ->setPROCESS('personas.obtener')
                        ->setDATA($row)
                        ->toArray()
                );
            }

            $where_list = ['1=1'];
            $params     = [];

            if ($ESTADO !== '') {
                $where_list[] = 'ESTADO = ?';
                $params[] = $ESTADO;
            }
            if ($ID_PERSONA !== '') {
                $where_list[] = "REPLACE(ID_PERSONA, '-', '') = ?";
                $params[] = $ID_PERSONA;
            }
            if ($NOMBRE !== '') {
                $parts = array_filter(explode(' ', $NOMBRE), function ($v) {
                    return trim($v) !== '';
                });
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

            $where = implode(' AND ', $where_list);
            $data = [];

            try {
                $pdo = \data_base();
                $stmt = $pdo->prepare(
                    "SELECT ID_PERSONA, NOMBRE, TELEFONO, CORREO, ESTADO
                       FROM tpersonas
                      WHERE {$where}
                   ORDER BY NOMBRE ASC"
                );
                $stmt->execute($params);
                $data = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\PDOException $e) {
                throw $e;
            }

            return $this->response->setJSON(
                Success('Listado de personas consultado correctamente', 'Consulta exitosa')
                    ->setPROCESS('personas.obtener')
                    ->setDATA($data)
                    ->toArray()
            );
        } catch (\Throwable $e) {
            log_message('error', 'Error al obtener personas: {error}', ['error' => $e->getMessage()]);

            return $this->response->setStatusCode(500)->setJSON(
                Danger('Error interno: ' . $e->getMessage(), 'Error del servidor')
                    ->setPROCESS('personas.obtener')
                    ->setDATA([])
                    ->setERRORS(['exception' => $e->getMessage()])
                    ->toArray()
            );
        }
    }
    public function buscar_por_cedula()
    {
        is_logged_in();

        try {
            $cedulaLimpia = $this->normalizarCedula($_GET['cedula'] ?? '');
            if ($cedulaLimpia === '') {
                return $this->response->setJSON(
                    Warning('Debe indicar la cédula a consultar', 'Solicitud incompleta')
                        ->setDATA([])
                        ->setPROCESS('personas.buscar_por_cedula')
                        ->toArray()
                );
            }

            $row = $this->obtenerPersonaPorCedulaLimpia($cedulaLimpia);

            if ($row === null) {
                return $this->response->setJSON(
                    Warning('No se localizaron datos para la cédula consultada', 'Sin resultados')
                        ->setDATA([])
                        ->setPROCESS('personas.buscar_por_cedula')
                        ->toArray()
                );
            }

            return $this->response->setJSON(
                Success('Persona encontrada correctamente', 'Consulta exitosa')
                    ->setPROCESS('personas.buscar_por_cedula')
                    ->setDATA($row)
                    ->toArray()
            );
        } catch (\Throwable $e) {
            log_message('error', 'Error al consultar persona por cédula: {error}', ['error' => $e->getMessage()]);

            return $this->response->setStatusCode(500)->setJSON(
                Danger('Error interno: ' . $e->getMessage(), 'Error del servidor')
                    ->setPROCESS('personas.buscar_por_cedula')
                    ->setDATA([])
                    ->setERRORS(['exception' => $e->getMessage()])
                    ->toArray()
            );
        }
    }

    public function guardar()
    {
        is_logged_in();

        if (!validar_permiso(['PE0001'])) {
            return $this->response->setJSON(Danger('No posees permisos para realizar esa acción')->toArray());
        }
        $ID_PERSONA = $this->normalizarCedula($_POST['ID_PERSONA'] ?? '');
        $NOMBRE     = trim($_POST['NOMBRE']     ?? '');
        $TELEFONO   = trim($_POST['TELEFONO']   ?? '');
        $CORREO     = trim($_POST['CORREO']     ?? '');

        if ($ID_PERSONA === '' || $NOMBRE === '') {
            return $this->response->setJSON(
                Warning('Cédula y Nombre son obligatorios')
                    ->setPROCESS('personas.guardar')
                    ->toArray()
            );
        }

        if ($this->obtenerPersonaPorCedulaLimpia($ID_PERSONA) !== null) {
            return $this->response->setJSON(
                Warning('Ya existe una persona con esa cédula')
                    ->setPROCESS('personas.guardar')
                    ->toArray()
            );
        }

        try {
            $pdo = \data_base();
            $stmt = $pdo->prepare(
                "INSERT INTO tpersonas (ID_PERSONA, NOMBRE, TELEFONO, CORREO, ESTADO)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $ID_PERSONA,
                $NOMBRE,
                $TELEFONO !== '' ? $TELEFONO : null,
                $CORREO   !== '' ? $CORREO   : null,
                'ACT',
            ]);
        } catch (\PDOException $th) {
            log_message('error', 'Error al insertar persona: {error}', ['error' => $th->getMessage()]);

            return $this->response->setJSON(
                Danger('No fue posible crear la persona: ' . $th->getMessage())
                    ->setPROCESS('personas.guardar')
                    ->toArray()
            );
        }

        return $this->response->setJSON(
            Success('Persona creada correctamente')
                ->setPROCESS('personas.guardar')
                ->toArray()
        );
    }

    public function editar()
    {
        is_logged_in();

        if (!validar_permiso(['PE0002'])) {
            return $this->response->setJSON(Danger('No posees permisos para realizar esa acción')->toArray());
        }

        $ID_PERSONA = $this->normalizarCedula($_POST['ID_PERSONA'] ?? '');
        $ID_ORIGINAL = $this->normalizarCedula($_POST['ID'] ?? '') ?: $ID_PERSONA;
        $NOMBRE     = trim($_POST['NOMBRE']     ?? '');
        $TELEFONO   = trim($_POST['TELEFONO']   ?? '');
        $CORREO     = trim($_POST['CORREO']     ?? '');

        if ($ID_PERSONA === '' || $NOMBRE === '') {
            return $this->response->setJSON(
                Warning('Campos incompletos')
                    ->setPROCESS('personas.editar')
                    ->toArray()
            );
        }

        if ($ID_PERSONA !== $ID_ORIGINAL && $this->obtenerPersonaPorCedulaLimpia($ID_PERSONA) !== null) {
            return $this->response->setJSON(
                Warning('Ya existe una persona con la nueva cédula ingresada')
                    ->setPROCESS('personas.editar')
                    ->toArray()
            );
        }

        $valores = [
            $ID_PERSONA,
            $NOMBRE,
            $TELEFONO !== '' ? $TELEFONO : null,
            $CORREO   !== '' ? $CORREO   : null,
        ];
        $set = [
            'ID_PERSONA = ?',
            'NOMBRE = ?',
            'TELEFONO = ?',
            'CORREO = ?',
        ];

        $estado = isset($_POST['ESTADO']) ? trim($_POST['ESTADO']) : '';
        if ($estado !== '') {
            $set[] = 'ESTADO = ?';
            $valores[] = $estado;
        }

        $valores[] = $ID_ORIGINAL;

        try {
            $pdo = \data_base();
            $stmt = $pdo->prepare(
                'UPDATE tpersonas SET ' . implode(', ', $set) . " WHERE REPLACE(ID_PERSONA, '-', '') = ?"
            );
            $stmt->execute($valores);

            if ($stmt->rowCount() > 0) {
                return $this->response->setJSON(
                    Success('Persona actualizada correctamente')
                        ->setPROCESS('personas.editar')
                        ->toArray()
                );
            }
        } catch (\PDOException $e) {
            log_message('error', 'Error al actualizar persona: {error}', ['error' => $e->getMessage()]);

            return $this->response->setJSON(
                Danger('No fue posible actualizar la persona: ' . $e->getMessage())
                    ->setPROCESS('personas.editar')
                    ->toArray()
            );
        }

        return $this->response->setJSON(
            Warning('No han habido cambios en el registro')
                ->setPROCESS('personas.editar')
                ->toArray()
        );
    }

    public function remover()
    {
        is_logged_in();

        if (!validar_permiso(['PE0003'])) {
            return $this->response->setJSON(Danger('No posees permisos para realizar esa acción')->toArray());
        }
        $ID_PERSONA = $this->normalizarCedula($_POST['idpersona'] ?? '');
        if ($ID_PERSONA === '') {
            return $this->response->setJSON(
                Warning('Solicitud inválida')
                    ->setPROCESS('personas.remover')
                    ->toArray()
            );
        }
        $persona = $this->obtenerPersonaPorCedulaLimpia($ID_PERSONA);

        if ($persona === null) {
            return $this->response->setJSON(
                Warning('No se encontró la persona solicitada')
                    ->setPROCESS('personas.remover')
                    ->toArray()
            );
        }

        if (($persona['ESTADO'] ?? '') === 'INC') {
            return $this->response->setJSON(
                Warning('La persona ya se encuentra inactiva')
                    ->setPROCESS('personas.remover')
                    ->toArray()
            );
        }

        try {
            $pdo = \data_base();
            $stmt = $pdo->prepare("UPDATE tpersonas SET ESTADO = 'INC' WHERE REPLACE(ID_PERSONA, '-', '') = ?");
            $stmt->execute([$ID_PERSONA]);

            if ($stmt->rowCount() > 0) {
                return $this->response->setJSON(
                    Success('Persona inactivada correctamente')
                        ->setPROCESS('personas.remover')
                        ->toArray()
                );
            }
        } catch (\PDOException $e) {
            log_message('error', 'Error al inactivar persona: {error}', ['error' => $e->getMessage()]);

            return $this->response->setJSON(
                Danger('No ha sido posible inactivar el registro: ' . $e->getMessage())
                    ->setPROCESS('personas.remover')
                    ->toArray()
            );
        }

        return $this->response->setJSON(
            Warning('No ha sido posible inactivar el registro')
                ->setPROCESS('personas.remover')
                ->toArray()
        );
    }
}

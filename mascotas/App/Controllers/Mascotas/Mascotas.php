<?php
namespace App\Controllers\Mascotas;

use App\Controllers\BaseController;

class Mascotas extends BaseController
{
    public function listado()
    {
        return view('mascotas/mascotas', []);
    }

    public function obtener()
{
    is_logged_in();

    // Siempre JSON para DataTables
    header('Content-Type: application/json; charset=utf-8');

    try {
        $MASCOTAS = [];
        if (validar_permiso(['M0001', 'M0002', 'M0003'])) {
            $NOMBRE_MASCOTA = trim($_GET['nombre']     ?? '');
            $ID_PERSONA     = trim($_GET['idpersona']  ?? '');
            $ESTADO         = trim($_GET['estado']     ?? '');
            $ID_MASCOTA     = trim($_GET['idmascota']  ?? '');

            $MascotasModel = model('Mascotas\\MascotasModel');

            // Una sola mascota
            if ($ID_MASCOTA !== '') {
                $MASCOTA = $MascotasModel
                    ->select('m.ID_MASCOTA', 'm.ID_PERSONA', 'p.NOMBRE AS DUENNO', 'm.NOMBRE_MASCOTA', 'm.FOTO_URL', 'm.ESTADO')
                    ->from('tmascotas m')
                    ->join('tpersonas p', 'p.ID_PERSONA = m.ID_PERSONA')
                    ->where('m.ID_MASCOTA', $ID_MASCOTA)
                    ->toArray()
                    ->getFirstRow();

                echo json_encode($MASCOTA ?? []);
                exit; // 🔴 nada más debe imprimirse
            }

            // Lista
            $where_list = ['1=1'];
            $params     = [];

            if ($ESTADO !== '') {
                $where_list[] = 'm.ESTADO = ?';
                $params[] = $ESTADO;
            }
            if ($ID_PERSONA !== '') {
                $where_list[] = 'm.ID_PERSONA = ?';
                $params[] = $ID_PERSONA;
            }
            if ($NOMBRE_MASCOTA !== '') {
                $parts = array_filter(explode(' ', $NOMBRE_MASCOTA), fn($v) => trim($v) !== '');
                if (!empty($parts)) {
                    $like = [];
                    foreach ($parts as $v) {
                        $like[] = 'm.NOMBRE_MASCOTA LIKE ?';
                        $params[] = "%{$v}%";
                    }
                    $where_list[] = '(' . implode(' OR ', $like) . ')';
                }
            }

            $where = implode(' AND ', $where_list);

            // BaseModel->query() devuelve ARRAY
            $MASCOTAS = $MascotasModel->query(
                "SELECT
                    m.ID_MASCOTA,
                    m.ID_PERSONA,
                    p.NOMBRE AS DUENNO,
                    m.NOMBRE_MASCOTA,
                    m.FOTO_URL,
                    m.ESTADO
                 FROM tmascotas m
                 JOIN tpersonas p ON p.ID_PERSONA = m.ID_PERSONA
                 WHERE {$where}
                 ORDER BY p.NOMBRE ASC, m.NOMBRE_MASCOTA ASC",
                $params
            );
        }

        echo json_encode(['data' => $MASCOTAS]);
        exit; // 🔴 importante

    } catch (\Throwable $e) {
        // Para que DataTables no tire popup, siempre JSON válido
        http_response_code(200);
        echo json_encode([
            'data'  => [],
            'error' => 'Error interno: '.$e->getMessage()
        ]);
        exit; // 🔴 importante
    }
}


    public function guardar()
    {
        is_logged_in();

        if (!validar_permiso(['M0001'])) {
            return json_encode(Danger('No posees permisos para realizar esa acción')->toArray());
        }

        $ID_PERSONA     = trim($_POST['ID_PERSONA']     ?? '');
        $NOMBRE_MASCOTA = trim($_POST['NOMBRE_MASCOTA'] ?? '');
        $FOTO_URL       = trim($_POST['FOTO_URL']       ?? '');

        if ($ID_PERSONA === '' || $NOMBRE_MASCOTA === '') {
            return json_encode(Warning('Cédula del dueño y Nombre de mascota son obligatorios')->toArray());
        }

        $PM = model('Personas\\PersonasModel');
        $existe = $PM->select('ID_PERSONA')->from('tpersonas')->where('ID_PERSONA', $ID_PERSONA)->toArray()->getFirstRow();
        if (!$existe) {
            $NOMBRE_DUENNO = trim($_POST['NOMBRE_DUENNO'] ?? '');
            if ($NOMBRE_DUENNO === '') $NOMBRE_DUENNO = 'SIN NOMBRE';
            $PM->insert([
                'ID_PERSONA' => $ID_PERSONA,
                'NOMBRE'     => $NOMBRE_DUENNO,
                'TELEFONO'   => $_POST['TELEFONO_DUENNO'] ?? null,
                'CORREO'     => $_POST['CORREO_DUENNO']   ?? null,
            ]);
        }

        $resp = model('Mascotas\\MascotasModel')->insert([
            'ID_PERSONA'     => $ID_PERSONA,
            'NOMBRE_MASCOTA' => $NOMBRE_MASCOTA,
            'FOTO_URL'       => $FOTO_URL ?: null,
            'ESTADO'         => 'ACT'
        ]);

        if (!empty($resp)) return json_encode(Success('Mascota registrada correctamente')->toArray());
        return json_encode(Warning('No ha sido posible registrar la mascota, intentalo de nuevo más tarde')->toArray());
    }

    public function editar()
    {
        is_logged_in();

        if (!validar_permiso(['M0002'])) {
            return json_encode(Danger('No posees permisos para realizar esa acción')->toArray());
        }

        $ID_MASCOTA     = (int)($_POST['ID_MASCOTA']    ?? 0);
        $ID_PERSONA     = trim($_POST['ID_PERSONA']     ?? '');
        $NOMBRE_MASCOTA = trim($_POST['NOMBRE_MASCOTA'] ?? '');
        $FOTO_URL       = trim($_POST['FOTO_URL']       ?? '');
        $ESTADO         = trim($_POST['ESTADO']         ?? '');

        if ($ID_MASCOTA <= 0 || $ID_PERSONA === '' || $NOMBRE_MASCOTA === '') {
            return json_encode(Warning('Campos incompletos')->toArray());
        }

        $data = [
            'ID_MASCOTA'     => $ID_MASCOTA,
            'ID_PERSONA'     => $ID_PERSONA,
            'NOMBRE_MASCOTA' => $NOMBRE_MASCOTA,
            'FOTO_URL'       => $FOTO_URL ?: null,
        ];
        if ($ESTADO !== '') {
            $data['ESTADO'] = $ESTADO;
        }

        $resp = model('Mascotas\\MascotasModel')->update($data, $ID_MASCOTA);
        if (!empty($resp)) return json_encode(Success('Mascota actualizada correctamente')->toArray());
        return json_encode(Warning('No han habido cambios en el registro')->toArray());
    }

    public function remover()
    {
        is_logged_in();

        if (!validar_permiso(['M0003'])) {
            return json_encode(Danger('No posees permisos para realizar esa acción')->toArray());
        }

        $ID_MASCOTA = (int)($_POST['idmascota'] ?? 0);
        if ($ID_MASCOTA <= 0) return json_encode(Warning('Solicitud inválida')->toArray());

        $resp = model('Mascotas\\MascotasModel')->update(['ESTADO' => 'INC'], $ID_MASCOTA);
        if (!empty($resp)) return json_encode(Success('Mascota desactivada correctamente')->toArray());
        return json_encode(Warning('No ha sido posible desactivar el registro, intentalo de nuevo más tarde')->toArray());
    }
}

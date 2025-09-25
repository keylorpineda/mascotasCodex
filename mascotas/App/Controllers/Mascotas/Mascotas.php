<?php

namespace App\Controllers\Mascotas;

use App\Controllers\BaseController;

class Mascotas extends BaseController
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
            "SELECT ID_PERSONA, NOMBRE, TELEFONO, CORREO, ESTADO
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

    private function procesarFoto(array $archivo, ?string $fotoActual = null): array
    {
        if (!isset($archivo['error']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            return [true, $fotoActual, null];
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return [false, null, 'No fue posible cargar la fotografía seleccionada'];
        }

        $tiposPermitidos = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        $mime = mime_content_type($archivo['tmp_name']);
        if ($mime === false || !array_key_exists($mime, $tiposPermitidos)) {
            return [false, null, 'El archivo seleccionado no es una imagen válida (jpg, png o webp)'];
        }

        $extension = $tiposPermitidos[$mime];
        $nombre    = uniqid('mascota_', true) . '.' . $extension;
        $directorioPublico = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'mascotas';

        if (!is_dir($directorioPublico) && !mkdir($directorioPublico, 0755, true) && !is_dir($directorioPublico)) {
            return [false, null, 'No fue posible preparar el directorio para almacenar la fotografía'];
        }

        $rutaDestino = $directorioPublico . DIRECTORY_SEPARATOR . $nombre;
        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return [false, null, 'No fue posible mover la fotografía al directorio de destino'];
        }

        if ($fotoActual !== null) {
            $rutaActual = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim(str_replace('public/', '', $fotoActual), DIRECTORY_SEPARATOR);
            if (is_file($rutaActual)) {
                @unlink($rutaActual);
            }
        }

        $rutaPublica = 'public/uploads/mascotas/' . $nombre;

        return [true, $rutaPublica, null];
    }

    public function listado()
    {
        return view('mascotas/mascotas', []);
    }

    public function obtener()
    {
        is_logged_in();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $MASCOTAS = [];
            if (validar_permiso(['M0001', 'M0002', 'M0003'])) {
                $NOMBRE_MASCOTA = trim($_GET['nombre']     ?? '');
                $ID_PERSONA     = $this->normalizarCedula($_GET['idpersona']  ?? '');
                $ESTADO         = trim($_GET['estado']     ?? 'ACT');
                $ID_MASCOTA     = trim($_GET['idmascota']  ?? '');

                $MascotasModel = model('Mascotas\\MascotasModel');

                if ($ID_MASCOTA !== '') {
                    $MASCOTA = $MascotasModel
                        ->table('tmascotas')
                        ->select(
                            'tmascotas.ID_MASCOTA',
                            'tmascotas.ID_PERSONA',
                            'tpersonas.NOMBRE AS DUENNO',
                            'tmascotas.NOMBRE_MASCOTA',
                            'tmascotas.FOTO_URL',
                            'tmascotas.ESTADO'
                        )
                        ->inner_join('tpersonas', 'tpersonas.ID_PERSONA', 'tmascotas.ID_PERSONA')
                        ->where('tmascotas.ID_MASCOTA', $ID_MASCOTA)
                        ->toArray()
                        ->getFirstRow();

                    if ($MASCOTA === null) {
                        echo json_encode(Warning('No se encontró la información solicitada', 'Sin resultados')->setPROCESS('mascotas.obtener')->setDATA([])->toArray());
                        exit;
                    }

                    echo json_encode(
                        Success('Mascota encontrada correctamente', 'Consulta exitosa')
                            ->setPROCESS('mascotas.obtener')
                            ->setDATA($MASCOTA)
                            ->toArray()
                    );
                    exit;
                }

                $where_list = ['1=1'];
                $params     = [];

                if ($ESTADO !== '') {
                    $where_list[] = 'm.ESTADO = ?';
                    $params[] = $ESTADO;
                }
                if ($ID_PERSONA !== '') {
                    $where_list[] = "REPLACE(m.ID_PERSONA, '-', '') = ?";
                    $params[] = $ID_PERSONA;
                }
                if ($NOMBRE_MASCOTA !== '') {
                    $parts = array_filter(explode(' ', $NOMBRE_MASCOTA), function ($v) {
                        return trim($v) !== '';
                    });
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
                if (is_object($MASCOTAS) && method_exists($MASCOTAS, 'getResultArray')) {
                    $MASCOTAS = $MASCOTAS->getResultArray();
                }
            }

            echo json_encode(
                Success('Listado de mascotas consultado correctamente', 'Consulta exitosa')
                    ->setPROCESS('mascotas.obtener')
                    ->setDATA($MASCOTAS)
                    ->toArray()
            );
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(
                Danger('Error interno: ' . $e->getMessage(), 'Error del servidor')
                    ->setPROCESS('mascotas.obtener')
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

        if (!validar_permiso(['M0001'])) {
            return json_encode(Danger('No posees permisos para realizar esa acción')->setPROCESS('mascotas.guardar')->toArray());
        }

        $ID_PERSONA     = $this->normalizarCedula($_POST['ID_PERSONA']     ?? '');
        $NOMBRE_MASCOTA = trim($_POST['NOMBRE_MASCOTA'] ?? '');
        $FOTO_ACTUAL    = trim($_POST['FOTO_ACTUAL']    ?? '');

        if ($ID_PERSONA === '' || $NOMBRE_MASCOTA === '') {
            return json_encode(Warning('Cédula del dueño y Nombre de mascota son obligatorios')->setPROCESS('mascotas.guardar')->toArray());
        }

        $archivoFoto = $_FILES['FOTO_ARCHIVO'] ?? null;
        $fotoFinal = $FOTO_ACTUAL !== '' ? $FOTO_ACTUAL : null;
        if ($archivoFoto !== null) {
            [$ok, $ruta, $error] = $this->procesarFoto($archivoFoto, null);
            if (!$ok) {
                return json_encode(Warning($error ?? 'No fue posible procesar la fotografía cargada')->setPROCESS('mascotas.guardar')->toArray());
            }
            $fotoFinal = $ruta ?? $fotoFinal;
        }
        $PM = model('Personas\\PersonasModel');
        $existe = $this->obtenerPersonaPorCedulaLimpia($ID_PERSONA);
        if (!$existe) {
            $NOMBRE_DUENNO   = trim($_POST['NOMBRE_DUENNO']   ?? '');
            $TELEFONO_DUENNO = trim($_POST['TELEFONO_DUENNO'] ?? '');
            $CORREO_DUENNO   = trim($_POST['CORREO_DUENNO']   ?? '');

            if ($NOMBRE_DUENNO === '' || $TELEFONO_DUENNO === '' || $CORREO_DUENNO === '') {
                return json_encode(Warning('Debe completar los datos del dueño antes de registrar la mascota')->setPROCESS('mascotas.guardar')->toArray());
            }
            $PM->insert([
                'ID_PERSONA' => $ID_PERSONA,
                'NOMBRE'     => $NOMBRE_DUENNO,
                'TELEFONO'   => $TELEFONO_DUENNO,
                'CORREO'     => $CORREO_DUENNO,
                'ESTADO'     => 'ACT',
            ]);
        }

        $resp = model('Mascotas\\MascotasModel')->insert([
            'ID_PERSONA'     => $ID_PERSONA,
            'NOMBRE_MASCOTA' => $NOMBRE_MASCOTA,
            'FOTO_URL'       => $fotoFinal !== null && $fotoFinal !== '' ? $fotoFinal : null,
            'ESTADO'         => 'ACT'
        ]);

        if (!empty($resp)) {
            return json_encode(Success('Mascota registrada correctamente')->setPROCESS('mascotas.guardar')->toArray());
        }
        return json_encode(Warning('No ha sido posible registrar la mascota, intentalo de nuevo más tarde')->setPROCESS('mascotas.guardar')->toArray());
    }

    public function editar()
    {
        is_logged_in();

        if (!validar_permiso(['M0002'])) {
            return json_encode(Danger('No posees permisos para realizar esa acción')->setPROCESS('mascotas.editar')->toArray());
        }

        $ID_MASCOTA     = (int)($_POST['ID_MASCOTA']    ?? 0);
        $ID_PERSONA     = $this->normalizarCedula($_POST['ID_PERSONA']     ?? '');
        $NOMBRE_MASCOTA = trim($_POST['NOMBRE_MASCOTA'] ?? '');
        $FOTO_ACTUAL    = trim($_POST['FOTO_ACTUAL']    ?? '');
        $ESTADO         = trim($_POST['ESTADO']         ?? '');

        if ($ID_MASCOTA <= 0 || $ID_PERSONA === '' || $NOMBRE_MASCOTA === '') {
            return json_encode(Warning('Campos incompletos')->setPROCESS('mascotas.editar')->toArray());
        }

        $MascotasModel = model('Mascotas\\MascotasModel');
        if ($this->obtenerPersonaPorCedulaLimpia($ID_PERSONA) === null) {
            return json_encode(Warning('Debe completar los datos del dueño antes de registrar la mascota')->setPROCESS('mascotas.editar')->toArray());
        }

        $registroActual = $MascotasModel
            ->select('FOTO_URL')
            ->where('ID_MASCOTA', $ID_MASCOTA)
            ->toArray()
            ->getFirstRow();

        $fotoActual = $FOTO_ACTUAL !== '' ? $FOTO_ACTUAL : ($registroActual['FOTO_URL'] ?? null);
        $archivoFoto = $_FILES['FOTO_ARCHIVO'] ?? null;
        if ($archivoFoto !== null) {
            [$ok, $ruta, $error] = $this->procesarFoto($archivoFoto, $fotoActual);
            if (!$ok) {
                return json_encode(Warning($error ?? 'No fue posible procesar la fotografía cargada')->setPROCESS('mascotas.editar')->toArray());
            }
            $fotoActual = $ruta ?? $fotoActual;
        }
        $data = [
            'ID_MASCOTA'     => $ID_MASCOTA,
            'ID_PERSONA'     => $ID_PERSONA,
            'NOMBRE_MASCOTA' => $NOMBRE_MASCOTA,
            'FOTO_URL'       => $fotoActual !== null && $fotoActual !== '' ? $fotoActual : null,
        ];
        if ($ESTADO !== '') {
            $data['ESTADO'] = $ESTADO;
        }

        $resp = $MascotasModel->update($data, $ID_MASCOTA);
        if (!empty($resp)) {
            return json_encode(Success('Mascota actualizada correctamente')->setPROCESS('mascotas.editar')->toArray());
        }
        return json_encode(Warning('No han habido cambios en el registro')->setPROCESS('mascotas.editar')->toArray());
    }

    public function remover()
    {
        is_logged_in();

        if (!validar_permiso(['M0003'])) {
            return json_encode(Danger('No posees permisos para realizar esa acción')->setPROCESS('mascotas.remover')->toArray());
        }

        $ID_MASCOTA = (int)($_POST['idmascota'] ?? 0);
        if ($ID_MASCOTA <= 0) {
            return json_encode(Warning('Solicitud inválida')->setPROCESS('mascotas.remover')->toArray());
        }

        $resp = model('Mascotas\\MascotasModel')->update(['ESTADO' => 'INC'], $ID_MASCOTA);
        if (!empty($resp)) {
            return json_encode(Success('Mascota desactivada correctamente')->setPROCESS('mascotas.remover')->toArray());
        }
        return json_encode(Warning('No ha sido posible desactivar el registro, intentalo de nuevo más tarde')->setPROCESS('mascotas.remover')->toArray());
    }
}

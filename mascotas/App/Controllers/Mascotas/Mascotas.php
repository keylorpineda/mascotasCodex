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

        header('Content-Type: application/json; charset=utf-8');

        try {
            $MASCOTAS = [];
            if (validar_permiso(['M0001', 'M0002', 'M0003'])) {
                $NOMBRE_MASCOTA = trim($_GET['nombre']     ?? '');
                $ID_PERSONA     = trim($_GET['idpersona']  ?? '');
                $ESTADO         = trim($_GET['estado']     ?? '');
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

                    echo json_encode($MASCOTA ?? []);
                    exit;
                }

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
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'data'  => [],
                'error' => 'Error interno: ' . $e->getMessage()
            ]);
            exit;
        }
    }


    public function guardar()
    {
        is_logged_in();

        if (!validar_permiso(['M0001'])) {
            return json_encode(Danger('No posees permisos para realizar esa acción')->toArray());
        }

        $ID_PERSONA      = trim($_POST['ID_PERSONA']      ?? '');
        $NOMBRE_MASCOTA  = trim($_POST['NOMBRE_MASCOTA']  ?? '');
        $FOTO_URL        = trim($_POST['FOTO_URL']        ?? '');
        $FOTO_ACTUAL     = trim($_POST['FOTO_ACTUAL']     ?? '');
        $NOMBRE_DUENNO   = trim($_POST['NOMBRE_DUENNO']   ?? '');
        $TELEFONO_DUENNO = trim($_POST['TELEFONO_DUENNO'] ?? '');
        $CORREO_DUENNO   = trim($_POST['CORREO_DUENNO']   ?? '');

        if ($ID_PERSONA === '' || $NOMBRE_MASCOTA === '') {
            return json_encode(Warning('Cédula del dueño y Nombre de mascota son obligatorios')->toArray());
        }

        if ($CORREO_DUENNO !== '' && !filter_var($CORREO_DUENNO, FILTER_VALIDATE_EMAIL)) {
            return json_encode(Warning('El correo del dueño debe ser válido')->toArray());
        }

        $fotoResult = $this->manejarCargaFoto($this->getFiles('FOTO_ARCHIVO'), $FOTO_ACTUAL !== '' ? $FOTO_ACTUAL : null);
        if ($fotoResult['error'] !== null) {
            return json_encode(Warning($fotoResult['error'])->toArray());
        }
        $debeEliminarAnterior = false;
        if (!empty($fotoResult['ruta']) && $fotoResult['ruta'] !== $FOTO_ACTUAL) {
            $debeEliminarAnterior = true;
            $FOTO_URL = $fotoResult['ruta'];
        }

        if (!$this->esFotoUrlValida($FOTO_URL)) {
            return json_encode(Warning('La referencia de la foto no es válida')->toArray());
        }

        if ($FOTO_ACTUAL !== '' && $FOTO_URL === '') {
            $debeEliminarAnterior = true;
        }
        if ($FOTO_ACTUAL !== '' && $FOTO_URL !== '' && $FOTO_URL !== $FOTO_ACTUAL && $this->esRutaInterna($FOTO_ACTUAL)) {
            $debeEliminarAnterior = true;
        }
        if ($debeEliminarAnterior) {
            $this->eliminarFotoLocal($FOTO_ACTUAL);
        }

        $PM = model('Personas\\PersonasModel');
        $existe = $PM->select('ID_PERSONA')->where('ID_PERSONA', $ID_PERSONA)->toArray()->getFirstRow();

        if (!$existe) {
            if ($NOMBRE_DUENNO === '' || $TELEFONO_DUENNO === '' || $CORREO_DUENNO === '') {
                return json_encode(Warning('Debe completar los datos del dueño antes de registrar la mascota')->toArray());
            }

            $PM->insert([
                'ID_PERSONA' => $ID_PERSONA,
                'NOMBRE'     => $NOMBRE_DUENNO,
                'TELEFONO'   => $TELEFONO_DUENNO,
                'CORREO'     => $CORREO_DUENNO,
                'ESTADO'     => 'ACT',
            ]);
        } else {
            $datosActualizar = [];
            if ($NOMBRE_DUENNO !== '') {
                $datosActualizar['NOMBRE'] = $NOMBRE_DUENNO;
            }
            if ($TELEFONO_DUENNO !== '') {
                $datosActualizar['TELEFONO'] = $TELEFONO_DUENNO;
            }
            if ($CORREO_DUENNO !== '') {
                $datosActualizar['CORREO'] = $CORREO_DUENNO;
            }
            if (!empty($datosActualizar)) {
                $datosActualizar['ID_PERSONA'] = $ID_PERSONA;
                $PM->update($datosActualizar);
            }
        }

        $resp = model('Mascotas\\MascotasModel')->insert([
            'ID_PERSONA'     => $ID_PERSONA,
            'NOMBRE_MASCOTA' => $NOMBRE_MASCOTA,
            'FOTO_URL'       => $FOTO_URL !== '' ? $FOTO_URL : null,
            'ESTADO'         => 'ACT'
        ]);

        if (!empty($resp)) {
            return json_encode(Success('Mascota registrada correctamente')->toArray());
        }

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
        $FOTO_ACTUAL    = trim($_POST['FOTO_ACTUAL']    ?? '');
        $ESTADO         = trim($_POST['ESTADO']         ?? '');

        if ($ID_MASCOTA <= 0 || $ID_PERSONA === '' || $NOMBRE_MASCOTA === '') {
            return json_encode(Warning('Campos incompletos')->toArray());
        }
        $fotoResult = $this->manejarCargaFoto($this->getFiles('FOTO_ARCHIVO'), $FOTO_ACTUAL !== '' ? $FOTO_ACTUAL : null);
        if ($fotoResult['error'] !== null) {
            return json_encode(Warning($fotoResult['error'])->toArray());
        }
        $debeEliminarAnterior = false;
        if (!empty($fotoResult['ruta']) && $fotoResult['ruta'] !== $FOTO_ACTUAL) {
            $debeEliminarAnterior = true;
            $FOTO_URL = $fotoResult['ruta'];
        }

        if (!$this->esFotoUrlValida($FOTO_URL)) {
            return json_encode(Warning('La referencia de la foto no es válida')->toArray());
        }
        if ($FOTO_ACTUAL !== '' && $FOTO_URL === '') {
            $debeEliminarAnterior = true;
        }
        if ($FOTO_ACTUAL !== '' && $FOTO_URL !== '' && $FOTO_URL !== $FOTO_ACTUAL && $this->esRutaInterna($FOTO_ACTUAL)) {
            $debeEliminarAnterior = true;
        }
        if ($debeEliminarAnterior) {
            $this->eliminarFotoLocal($FOTO_ACTUAL);
        }
        $data = [
            'ID_MASCOTA'     => $ID_MASCOTA,
            'ID_PERSONA'     => $ID_PERSONA,
            'NOMBRE_MASCOTA' => $NOMBRE_MASCOTA,
            'FOTO_URL'       => $FOTO_URL !== '' ? $FOTO_URL : null,
        ];
        if ($ESTADO !== '') {
            $data['ESTADO'] = $ESTADO;
        }

        $resp = model('Mascotas\\MascotasModel')->update($data, $ID_MASCOTA);
        if (!empty($resp)) {
            return json_encode(Success('Mascota actualizada correctamente')->toArray());
        }
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

    private function manejarCargaFoto(?array $archivo, ?string $rutaActual = null): array
    {
        if (empty($archivo) || !isset($archivo['error']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            return ['ruta' => $rutaActual, 'error' => null];
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return ['ruta' => null, 'error' => 'No se pudo cargar la imagen seleccionada'];
        }

        $ext = strtolower(pathinfo($archivo['name'] ?? '', PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $permitidas, true)) {
            return ['ruta' => null, 'error' => 'Formato de imagen no permitido'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $archivo['tmp_name']) : null;
        if ($finfo) {
            finfo_close($finfo);
        }
        $mimesPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if ($mime !== null && !in_array($mime, $mimesPermitidos, true)) {
            return ['ruta' => null, 'error' => 'El archivo seleccionado no parece ser una imagen'];
        }

        $destDir = base_dir('public/uploads/mascotas');
        if (!is_dir($destDir) && !mkdir($destDir, 0775, true) && !is_dir($destDir)) {
            return ['ruta' => null, 'error' => 'No se pudo preparar el directorio de imágenes'];
        }

        $nombre = uniqid('mascota_', true) . '.' . $ext;
        $destino = $destDir . DIRECTORY_SEPARATOR . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            return ['ruta' => null, 'error' => 'No se pudo guardar la imagen cargada'];
        }

        @chmod($destino, 0644);

        if ($rutaActual) {
            $rutaAnterior = base_dir('public/' . ltrim($rutaActual, '/'));
            if (is_file($rutaAnterior)) {
                @unlink($rutaAnterior);
            }
        }

        return ['ruta' => 'uploads/mascotas/' . $nombre, 'error' => null];
    }

    private function esFotoUrlValida(?string $url): bool
    {
        if ($url === null || $url === '') {
            return true;
        }

        $url = trim($url);
        if ($url === '') {
            return true;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $esHttp = in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true);
            return $esHttp;
        }

        return $this->esRutaInterna($url);
    }

    private function esRutaInterna(?string $ruta): bool
    {
        if ($ruta === null || $ruta === '') {
            return false;
        }

        $rutaNormalizada = ltrim($ruta, '/');
        return strncmp($rutaNormalizada, 'uploads/mascotas/', strlen('uploads/mascotas/')) === 0;
    }

    private function eliminarFotoLocal(?string $ruta): void
    {
        if (!$this->esRutaInterna($ruta)) {
            return;
        }

        $archivo = base_dir('public/' . ltrim((string)$ruta, '/'));
        if (is_file($archivo)) {
            @unlink($archivo);
        }
    }
}

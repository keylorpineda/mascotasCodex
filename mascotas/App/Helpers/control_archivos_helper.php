<?php
use App\Services\FileManagement\ControlArchivos;
// ========================================
// CONSTANTES Y CONFIGURACIÓN
// ========================================

if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB por defecto
}

if (!defined('ALLOWED_FILE_TYPES')) {
    define('ALLOWED_FILE_TYPES', [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
    ]);
}

if (!defined('ALLOWED_EXTENSIONS')) {
    define('ALLOWED_EXTENSIONS', [
        'jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx','ppt','pptx','zip','rar','7z',
    ]);
}

if (!function_exists("obtener_tipo_archivo_base64")) {
    function obtener_tipo_archivo_base64(string $base64String): ?string 
    {
        try {
            // Limpiar prefijo
            $base64String = preg_replace('/^data:[a-zA-Z0-9\/]+;base64,/', '', $base64String);

            // Decodificar
            $contenido = base64_decode($base64String, true);
            if (!$contenido) {
                return null;
            }

            // Obtener firma
            $firma = bin2hex(substr($contenido, 0, 4));

            return ControlArchivos::$firmasArchivos[$firma] ?? null;
        } catch (Exception $e) {
            ControlArchivos::logError("Error en obtener_tipo_archivo_base64", $e);
            return null;
        }
    }
}

if (!function_exists("es_imagen_base_64")) {
    function es_imagen_base_64(string $base64String): bool 
    {
        try {
            // Limpiar encabezado
            $base64String = preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
            $base64String = str_replace(' ', '+', $base64String);

            // Validar base64
            $decoded = base64_decode($base64String, true);
            if ($decoded === false || strlen($decoded) > MAX_FILE_SIZE) {
                return false;
            }

            // Validar como imagen
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($decoded);
            
            return (strpos($mimeType, 'image/') === 0);
        } catch (Exception $e) {
            ControlArchivos::logError("Error en es_imagen_base_64", $e);
            return false;
        }
    }
}

if (!function_exists("img_a_base64")) {
    function img_a_base64(string $rutaImagen): string 
    {
        try {
            if (!is_file($rutaImagen) || !is_readable($rutaImagen)) {
                return "";
            }

            $tipoImagen = pathinfo($rutaImagen, PATHINFO_EXTENSION);
            $datosImagen = file_get_contents($rutaImagen);
            
            if ($datosImagen === false) {
                return "";
            }

            return "data:image/{$tipoImagen};base64," . base64_encode($datosImagen);
        } catch (Exception $e) {
            ControlArchivos::logError("Error en img_a_base64: {$rutaImagen}", $e);
            return "";
        }
    }
}

if (!function_exists("file_to_base64")) {
    function file_to_base64(string $route): string 
    {
        try {
            if (!is_file($route) || !is_readable($route)) {
                return "";
            }

            $extension = pathinfo($route, PATHINFO_EXTENSION);
            $mimeType = ControlArchivos::obtenerMimeTypeSafe($route, $extension);
            $file_data = file_get_contents($route);
            
            if ($file_data === false) return "";

            return "data:{$mimeType};base64," . base64_encode($file_data);
        } catch (Exception $e) {
            ControlArchivos::logError("Error en file_to_base64: {$route}", $e);
            return "";
        }
    }
}

if (!function_exists("guardar_archivo_base_64")) {
    function guardar_archivo_base_64(string $base64Data, string $destination): bool 
    {
        return guardarArchivoBase64($base64Data, $destination);
    }

    function guardarArchivoBase64(string $base64Data, string $destination): bool 
    {
        try {
            // Extraer datos base64
            if (strpos($base64Data, ',') !== false) {
                $base64Data = explode(',', $base64Data)[1];
            }
            
            $binaryData = base64_decode($base64Data, true);
            
            if ($binaryData === false) {
                return false;
            }
            
            // Crear directorio si no existe
            $dir = dirname($destination);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            return file_put_contents($destination, $binaryData) !== false;
        } catch (Exception $e) {
            ControlArchivos::logError("Error en guardarArchivoBase64: {$destination}", $e);
            return false;
        }
    }
}

if (!function_exists("validar_archivo")) {
    function validar_archivo($file, ?array $allowedExtensions = null, bool $isBase64 = false): array
    {
        try {
            if ($isBase64) {
                return es_imagen_base_64($file)
                    ? ['status' => true, 'message' => 'Archivo base64 válido.']
                    : ['status' => false, 'message' => 'Archivo base64 inválido.'];
            }

            // Validar upload
            if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
                return ['status' => false, 'message' => 'Error al subir el archivo.'];
            }

            // Validar tamaño
            if ($file['size'] > MAX_FILE_SIZE) {
                return ['status' => false, 'message' => 'El archivo es demasiado grande.'];
            }

            // Validar tipo MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, ALLOWED_FILE_TYPES)) {
                return ['status' => false, 'message' => "Tipo de archivo no permitido: {$mimeType}"];
            }

            // Validar extensión
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $allowedExtensions = $allowedExtensions ?? ALLOWED_EXTENSIONS;
            
            if (!in_array(strtolower($extension), array_map('strtolower', $allowedExtensions))) {
                return ['status' => false, 'message' => "Extensión .{$extension} no permitida."];
            }

            return ['status' => true, 'message' => 'Archivo válido.'];
        } catch (Exception $e) {
            ControlArchivos::logError("Error en validar_archivo", $e);
            return ['status' => false, 'message' => 'Error interno al validar archivo.'];
        }
    }
}

if (!function_exists("validar_archivo_estricto")) {
    function validar_archivo_estricto($file): array 
    {
        try {
            if (!file_exists($file)) {
                return ["status" => false, "message" => "El archivo no existe."];
            }

            $mimeType = mime_content_type($file);
            if (!in_array($mimeType, ALLOWED_FILE_TYPES)) {
                return ["status" => false, "message" => "Tipo MIME no permitido: $mimeType."];
            }

            // Validar firma binaria
            $contenido = fopen($file, 'rb');
            $cabecera = fread($contenido, 8);
            fclose($contenido);

            if (isset(ControlArchivos::$firmasBinarias[$mimeType])) {
                $firmasEsperadas = ControlArchivos::$firmasBinarias[$mimeType];
                $valida = false;
                
                foreach ($firmasEsperadas as $firma) {
                    if (strncmp($cabecera, $firma, strlen($firma)) === 0) {
                        $valida = true;
                        break;
                    }
                }
                
                if (!$valida) {
                    return ["status" => false, "message" => "El archivo no tiene la firma válida para su tipo MIME ($mimeType)."];
                }
            }

            return ["status" => true, "message" => "El archivo es válido."];
        } catch (Exception $e) {
            ControlArchivos::logError("Error en validar_archivo_estricto: {$file}", $e);
            return ["status" => false, "message" => "Error interno al validar archivo."];
        }
    }
}

if (!function_exists("guardar_archivo")) {
    function guardar_archivo($file, ?string $destination = null, bool $isBase64 = false, ?array $allowedExtensions = null, ?string $customFileName = null): array
    {
        try {
            // 1. Validar archivo
            $validationResult = validar_archivo($file, $allowedExtensions, $isBase64);
            if (!$validationResult['status']) {
                return $validationResult;
            }

            // 2. Determinar extensión
            $extension = determinarExtensionArchivo($file, $isBase64);
            if (!$extension) {
                return ['status' => false, 'message' => 'No se pudo determinar la extensión del archivo.'];
            }

            // 3. Generar nombre
            $fileName = generarNombreArchivo($customFileName, $extension);

            // 4. Preparar directorio
            $destination = prepararDirectorioDestino($destination);
            if (!$destination['status']) {
                return $destination;
            }

            $fullPath = $destination['path'] . $fileName;

            // 5. Guardar archivo
            $saveResult = $isBase64 
                ? guardarArchivoBase64($file, $fullPath)
                : guardarArchivoUpload($file, $fullPath);

            if ($saveResult) {
                return [
                    'status' => true, 
                    'message' => 'Archivo guardado exitosamente.', 
                    'destination' => $fullPath,
                    'filename' => $fileName
                ];
            }

            return ['status' => false, 'message' => 'Error al guardar el archivo.'];

        } catch (Exception $e) {
            ControlArchivos::logError("Error en guardar_archivo", $e);
            return ['status' => false, 'message' => 'Error interno al procesar el archivo.'];
        }
    }

    function determinarExtensionArchivo($file, bool $isBase64): ?string 
    {
        if ($isBase64) {
            if (preg_match('/^data:image\/(\w+);base64,/', $file, $matches)) {
                $extension = $matches[1];
                return ($extension === 'jpeg') ? 'jpg' : $extension;
            }
            return 'png';
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        return strtolower($extension);
    }

    function generarNombreArchivo(?string $customFileName, string $extension): string 
    {
        if (!empty($customFileName)) {
            $sanitizedName = ControlArchivos::sanitizarNombre($customFileName);
            
            $fileExtension = pathinfo($sanitizedName, PATHINFO_EXTENSION);
            if (empty($fileExtension)) {
                $sanitizedName .= '.' . $extension;
            }
            
            return $sanitizedName;
        }
        
        return ControlArchivos::generarNombreUnico($extension);
    }

    function prepararDirectorioDestino(?string $destination): array 
    {
        $finalDestination = $destination ?? (defined('WRITER_DIR') ? WRITER_DIR . '/' : './');
        $finalDestination = rtrim($finalDestination, '/') . '/';
        
        if (!file_exists($finalDestination)) {
            if (!mkdir($finalDestination, 0755, true)) {
                return ['status' => false, 'message' => 'No se pudo crear el directorio de destino.'];
            }
        }
        
        if (!is_writable($finalDestination)) {
            return ['status' => false, 'message' => 'El directorio de destino no tiene permisos de escritura.'];
        }
        
        return ['status' => true, 'path' => $finalDestination];
    }

    function guardarArchivoUpload(array $fileData, string $destination): bool 
    {
        if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
            return false;
        }
        
        return move_uploaded_file($fileData['tmp_name'], $destination);
    }
}

if (!function_exists("mover_archivo")) {
    function mover_archivo($file, string $destination, ?string $targetPath = null): array
    {
        try {
            $fileName = basename($file['name']);
            $finalPath = $targetPath ? $targetPath . '/' . $fileName : $destination . '/' . $fileName;

            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $finalPath)) {
                return ['status' => true, 'message' => 'Archivo movido exitosamente.'];
            }
            
            return ['status' => false, 'message' => 'No se pudo mover el archivo.'];
        } catch (Exception $e) {
            ControlArchivos::logError("Error en mover_archivo", $e);
            return ['status' => false, 'message' => 'Error interno al mover archivo.'];
        }
    }
}

if (!function_exists("copiar_archivo")) {
    function copiar_archivo($source, string $destination, ?string $targetPath = null): array
    {
        try {
            $fileName = basename($source);
            
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }

            $finalDestination = $destination . "/" . $fileName;
            
            if (copy($source, $finalDestination)) {
                return [
                    'status' => true, 
                    'message' => 'Archivo copiado exitosamente.', 
                    'destination' => $finalDestination
                ];
            }
            
            return ['status' => false, 'message' => 'No se pudo copiar el archivo.'];
        } catch (Exception $e) {
            ControlArchivos::logError("Error en copiar_archivo: {$source}", $e);
            return ['status' => false, 'message' => 'Error interno al copiar archivo.'];
        }
    }
}

if (!function_exists("is_path_valid")) {
    function is_path_valid(string $path, ?string $realBase = null): bool
    {
        $realBase = $realBase ?? (defined('WRITER_DIR') ? realpath(WRITER_DIR) : '');
        return ControlArchivos::validarRutaSegura($path, $realBase);
    }
}

if (!function_exists("eliminar_archivos")) {
    function eliminar_archivos(string $filePath, bool $deleteDirectory = false, ?string $validPath = null): array
    {
        try {
            $filePath = realpath($filePath);
            if (!is_path_valid($filePath, $validPath)) {
                return ['status' => false, 'message' => 'Ruta no válida o fuera del directorio permitido.'];
            }

            if ($deleteDirectory) {
                return eliminar_directorios($filePath);
            } elseif (is_file($filePath)) {
                if (unlink($filePath)) {
                    return ['status' => true, 'message' => 'Archivo eliminado exitosamente.'];
                }
                return ['status' => false, 'message' => 'No se pudo eliminar el archivo.'];
            }
            
            return ['status' => false, 'message' => 'El archivo o directorio no existe.'];
        } catch (Exception $e) {
            ControlArchivos::logError("Error en eliminar_archivos: {$filePath}", $e);
            return ['status' => false, 'message' => 'Error interno al eliminar archivo.'];
        }
    }
}

if (!function_exists("eliminar_directorios")) {
    function eliminar_directorios(string $dir): array
    {
        try {
            if (!is_path_valid($dir)) {
                return ['status' => false, 'message' => 'Ruta no válida o fuera del directorio permitido.'];
            }

            $files = array_diff(scandir($dir), ['.', '..']);
            
            foreach ($files as $file) {
                $filePath = "$dir/$file";
                if (is_dir($filePath)) {
                    $result = eliminar_directorios($filePath);
                    if (!$result['status']) {
                        return $result;
                    }
                } else {
                    if (!unlink($filePath)) {
                        return ['status' => false, 'message' => "No se pudo eliminar el archivo: $filePath"];
                    }
                }
            }

            if (rmdir($dir)) {
                return ['status' => true, 'message' => 'Directorio eliminado exitosamente.'];
            }
            
            return ['status' => false, 'message' => 'No se pudo eliminar el directorio.'];
        } catch (Exception $e) {
            ControlArchivos::logError("Error en eliminar_directorios: {$dir}", $e);
            return ['status' => false, 'message' => 'Error interno al eliminar directorio.'];
        }
    }
}

if (!function_exists("descargar_archivos")) {
    function descargar_archivos(string $rutaCarpeta, bool $comprimir = true, bool $descargar = true)
    {
        try {
            if (!is_dir($rutaCarpeta) || !is_path_valid($rutaCarpeta)) {
                http_response_code(400);
                exit("La ruta proporcionada no es válida.");
            }

            $archivos = array_diff(scandir($rutaCarpeta), ['.', '..']);

            if (empty($archivos)) {
                http_response_code(404);
                exit("La carpeta está vacía.");
            }

            if ($comprimir) {
                $zipNombre = 'descarga_' . date('Ymd_His') . '.zip';
                $zipRuta = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipNombre;

                $zip = new ZipArchive();

                if ($zip->open($zipRuta, ZipArchive::CREATE) !== TRUE) {
                    http_response_code(500);
                    exit("No se pudo crear el archivo ZIP.");
                }

                foreach ($archivos as $archivo) {
                    $archivoRuta = $rutaCarpeta . DIRECTORY_SEPARATOR . $archivo;
                    if (is_file($archivoRuta)) {
                        $zip->addFile($archivoRuta, $archivo);
                    }
                }

                $zip->close();

                if (!$descargar) {
                    return $zipRuta;
                }

                // Headers para descarga
                header('Content-Type: application/zip');
                header("Content-Disposition: attachment; filename=\"$zipNombre\"");
                header('Content-Length: ' . filesize($zipRuta));
                header('X-Content-Type-Options: nosniff');
                header('X-Frame-Options: DENY');

                readfile($zipRuta);
                unlink($zipRuta);
            } else {
                echo "<h3>Archivos disponibles para descargar:</h3><ul>";
                foreach ($archivos as $archivo) {
                    $rutaRelativa = $rutaCarpeta . DIRECTORY_SEPARATOR . $archivo;
                    $archivoNombre = basename($rutaRelativa);
                    echo "<li><a href='$rutaRelativa' download>$archivoNombre</a></li>";
                }
                echo "</ul>";
            }
        } catch (Exception $e) {
            ControlArchivos::logError("Error en descargar_archivos: {$rutaCarpeta}", $e);
            http_response_code(500);
            exit("Error interno al procesar la descarga.");
        }
    }
}

if (!function_exists("descargar_archivo_unico")) {
    function descargar_archivo_unico(string $filePath, ?string $customName = null, bool $returnAsString = false): ?string
    {
        try {
            // Validar constantes
            if (!defined('ALLOWED_EXTENSIONS') || !is_array(ALLOWED_EXTENSIONS)) {
                throw new Exception("La constante ALLOWED_EXTENSIONS no está definida o no es un array");
            }

            // Verificar directorio writer
            $writerDir = function_exists('base_dir') ? realpath(base_dir("writer")) : null;
            if ($writerDir === false) {
                throw new Exception("El directorio 'writer' no existe o no es accesible");
            }

            // Sanitizar ruta
            $filePath = ltrim($filePath, '/');

            // Validar path traversal
            if (!str_contains($filePath, $writerDir)) {
                throw new Exception("Acceso denegado: intento de acceso fuera del directorio permitido");
            }

            // Validar archivo
            if (!is_file($filePath)) {
                throw new Exception("El archivo especificado no existe o no es un archivo válido");
            }

            $realPath = realpath($filePath);
            if (!is_readable($realPath)) {
                throw new Exception("No se tienen permisos para leer el archivo");
            }

            // Validar extensión
            $fileExtension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
            if (empty($fileExtension)) {
                throw new Exception("El archivo no tiene una extensión válida");
            }

            $allowedExtensions = array_map('strtolower', ALLOWED_EXTENSIONS);
            if (!in_array($fileExtension, $allowedExtensions)) {
                throw new Exception("Tipo de archivo no permitido. Extensiones permitidas: " . implode(', ', ALLOWED_EXTENSIONS));
            }

            // Validar tamaño
            $maxFileSize = 100 * 1024 * 1024; // 100MB
            $fileSize = filesize($realPath);
            
            if ($fileSize === false) {
                throw new Exception("No se puede determinar el tamaño del archivo");
            }

            if ($fileSize > $maxFileSize) {
                throw new Exception("El archivo es demasiado grande para ser descargado");
            }

            if ($fileSize === 0) {
                throw new Exception("El archivo está vacío");
            }

            // Determinar nombre del archivo para descarga
            $downloadName = $customName ?: basename($realPath);
            
            // Sanitizar nombre personalizado si se proporciona
            if ($customName !== null) {
                $downloadName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $customName);
                
                // Asegurar que mantenga la extensión original si no se especifica
                if (pathinfo($downloadName, PATHINFO_EXTENSION) === '') {
                    $downloadName .= '.' . $fileExtension;
                }
            }

            /**
             * Función auxiliar para obtener tipo MIME de manera segura
             * 
             * @param string $filePath Ruta completa del archivo
             * @param string $extension Extensión del archivo
             * @return string Tipo MIME
             */
            function getMimeType(string $filePath, string $extension): string
            {
                // Intentar usar finfo si está disponible (más seguro)
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        if ($finfo !== false) {
                            $detectedMime = finfo_file($finfo, $filePath);
                            finfo_close($finfo);
                            
                            if ($detectedMime !== false && !empty($detectedMime)) {
                                return $detectedMime;
                            }
                        }
                    }

                // Mapeo seguro de extensiones a tipos MIME
                    $mimeTypes = [
                        'pdf'  => 'application/pdf',
                        'doc'  => 'application/msword',
                        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'xls'  => 'application/vnd.ms-excel',
                        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'ppt'  => 'application/vnd.ms-powerpoint',
                        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'jpg'  => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'png'  => 'image/png',
                        'zip'  => 'application/zip',
                        'rar'  => 'application/x-rar-compressed',
                        '7z'   => 'application/x-7z-compressed',
                    ];

                // Fallback al mapeo manual
                return $mimeTypes[$extension] ?? 'application/octet-stream';
            }

            // Obtener tipo MIME de manera segura
            $mimeType = getMimeType($realPath, $fileExtension);

            // Si se solicita como string, leer y retornar contenido
            if ($returnAsString) {
                $content = file_get_contents($realPath);
                if ($content === false) {
                    throw new Exception("Error al leer el contenido del archivo");
                }
                return $content;
            }

            // Limpiar buffer de salida para evitar corrupción
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Configurar headers seguros para descarga
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . addslashes($downloadName) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . $fileSize);
            
            // Headers adicionales de seguridad
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');

            // Leer archivo en chunks para manejar archivos grandes eficientemente
            $handle = fopen($realPath, 'rb');
            if ($handle === false) {
                throw new Exception("No se puede abrir el archivo para lectura");
            }

            while (!feof($handle)) {
                $chunk = fread($handle, 8192); // Leer en chunks de 8KB
                if ($chunk === false) {
                    fclose($handle);
                    throw new Exception("Error al leer el archivo");
                }
                echo $chunk;
                flush();
            }

            fclose($handle);
            exit;
        } catch (Exception $e) {
            ControlArchivos::logError("Error en descargar_archivo_unico: {$rutaCarpeta}", $e);
            http_response_code(500);
            exit("Error interno al procesar la descarga.");
        }
    }
}
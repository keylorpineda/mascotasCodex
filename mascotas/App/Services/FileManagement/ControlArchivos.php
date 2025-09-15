<?php
namespace App\Services\FileManagement;

class ControlArchivos 
{
    private static $errorLog = null;
    
    /**
     * Mapeo de firmas hexadecimales a tipos MIME
     */
    private static $firmasArchivos = [
        '25504446' => 'application/pdf',
        '89504e47' => 'image/png',
        'ffd8ffe0' => 'image/jpeg',
        'ffd8ffe1' => 'image/jpeg',
        'ffd8ffe2' => 'image/jpeg',
        'ffd8ffe3' => 'image/jpeg',
        'ffd8ffe8' => 'image/jpeg',
        '47494638' => 'image/gif',
        '504b0304' => 'application/zip',
        'd0cf11e0' => 'application/msword',
    ];

    /**
     * Firmas binarias para validación estricta
     */
    private static $firmasBinarias = [
        'image/jpeg' => ["\xFF\xD8\xFF"],
        'image/png' => ["\x89\x50\x4E\x47"],
        'image/gif' => ["GIF87a", "GIF89a"],
        'application/pdf' => ["%PDF"],
        'application/msword' => ["\xD0\xCF\x11\xE0"],
        'application/vnd.ms-excel' => ["\xD0\xCF\x11\xE0"],
        'application/vnd.ms-powerpoint' => ["\xD0\xCF\x11\xE0"],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ["PK\x03\x04"],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ["PK\x03\x04"],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ["PK\x03\x04"],
        'application/zip' => ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"],
        'application/x-rar-compressed' => ["Rar!\x1A\x07\x00", "Rar!\x1A\x07\x01\x00"],
        'application/x-7z-compressed' => ["\x37\x7A\xBC\xAF\x27\x1C"],
    ];

    /**
     * Mapeo de extensiones a tipos MIME
     */
    private static $mimeTypes = [
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
        'gif'  => 'image/gif',
        'zip'  => 'application/zip',
        'rar'  => 'application/x-rar-compressed',
        '7z'   => 'application/x-7z-compressed',
    ];

    /**
     * Inicializa el log de errores
     */
    private static function initErrorLog(): void
    {
        if (self::$errorLog === null) {
            self::$errorLog = function_exists('base_dir') 
                ? base_dir("writer/logs/control_archivos.log", true)
                : sys_get_temp_dir() . '/control_archivos.log';
            
            if (!is_file(self::$errorLog)) {
                $dir = dirname(self::$errorLog);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }
        }
    }

    /**
     * Registra errores en el log
     */
    private static function logError(string $message, ?Exception $e = null): void
    {
        self::initErrorLog();
        
        $logMessage = sprintf(
            "[%s] %s",
            date("Y-m-d H:i:s"),
            $message
        );

        if ($e) {
            $logMessage .= sprintf(
                " | Error: %s | Archivo: %s | Línea: %d",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
        }

        error_log($logMessage, 3, self::$errorLog);
        error_log($message); // También al log del sistema
    }

    /**
     * Obtiene tipo MIME de manera segura
     */
    public static function obtenerMimeTypeSafe(string $filePath, string $extension): string
    {
        // Intentar usar finfo si está disponible
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

        // Fallback al mapeo manual
        return self::$mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Valida ruta de archivo de manera segura
     */
    public static function validarRutaSegura(string $path, ?string $baseDir = null): bool
    {
        $baseDir = $baseDir ?? (defined('WRITER_DIR') ? WRITER_DIR : '');
        
        if (empty($baseDir)) {
            return false;
        }

        $realBase = realpath($baseDir);
        $realUserPath = realpath($path);
        
        return ($realUserPath !== false && $realBase !== false && strpos($realUserPath, $realBase) === 0);
    }

    /**
     * Sanitiza nombre de archivo
     */
    public static function sanitizarNombre(string $fileName): string
    {
        // Remover caracteres peligrosos
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        
        // Evitar nombres problemáticos
        $sanitized = trim($sanitized, '.');
        
        // Limitar longitud
        if (strlen($sanitized) > 100) {
            $sanitized = substr($sanitized, 0, 100);
        }
        
        return empty($sanitized) ? 'archivo' : $sanitized;
    }

    /**
     * Genera nombre único para archivo
     */
    public static function generarNombreUnico(string $extension): string
    {
        return bin2hex(random_bytes(16)) . '.' . ltrim($extension, '.');
    }
}
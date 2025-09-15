<?php

namespace App\Services\Auth;

use Exception;

class SecureSaltManager {
    
    private static $salt_file_path;
    private static $max_salt_length = 128;
    private static $min_salt_length = 32;
    
    public static function init($config_dir = null) {
        if ($config_dir === null) {
            $config_dir = base_dir('App\config');
        }
        
        self::$salt_file_path = realpath($config_dir) . DIRECTORY_SEPARATOR . 'app_salt.dat';
        
        // Crear directorio si no existe
        if (!is_dir(dirname(self::$salt_file_path))) {
            mkdir(dirname(self::$salt_file_path), 0755, true);
        }
        
        self::loadOrGenerateSalt();
    }
    
    private static function loadOrGenerateSalt() {
        if (!defined('APP_SALT')) {
            $salt = self::loadSaltFromFile();
            
            if ($salt === false) {
                $salt = self::generateAndStoreSalt();
            }
            
            define('APP_SALT', $salt);
        }
    }
    
    private static function loadSaltFromFile() {
        if (!file_exists(self::$salt_file_path)) {
            return false;
        }
        
        // Verificar permisos del archivo
        if (!is_readable(self::$salt_file_path)) {
            error_log("Salt file is not readable: " . self::$salt_file_path, 3, base_dir('writer/logs/salt_errors.log'));
            return false;
        }
        
        // Verificar que el archivo no sea demasiado grande (protección contra DoS)
        $file_size = filesize(self::$salt_file_path);
        if ($file_size > 1024) { // 1KB máximo
            error_log("Salt file is too large: " . $file_size . " bytes", 3, base_dir('writer/logs/salt_errors.log'));
            return false;
        }
        
        // Leer contenido de forma segura
        $content = file_get_contents(self::$salt_file_path);
        if ($content === false) {
            error_log("Failed to read salt file", 3, base_dir('writer/logs/salt_errors.log'));
            return false;
        }
        
        // Decodificar y validar
        $data = self::decodeSaltData($content);
        if ($data === false) {
            error_log("Invalid salt file format", 3, base_dir('writer/logs/salt_errors.log'));
            return false;
        }
        
        return $data['salt'];
    }
    
    private static function generateAndStoreSalt() {
        $salt = self::generateSecureSalt();
        
        if (self::storeSaltToFile($salt)) {
            return $salt;
        }
        
        // Si falla el almacenamiento, usar salt temporal
        error_log("Failed to store salt file, using temporary salt", 3, base_dir('writer/logs/salt_errors.log'));
        return self::generateSecureSalt();
    }
    
    private static function generateSecureSalt($length = 64) {
        // Método 1: random_bytes (más seguro)
        if (function_exists('random_bytes')) {
            try {
                return bin2hex(random_bytes($length / 2));
            } catch (Exception $e) {
                error_log("random_bytes failed: " . $e->getMessage(), 3, base_dir('writer/logs/salt_errors.log'));
            }
        }
        
        // Método 2: openssl_random_pseudo_bytes
        if (function_exists('openssl_random_pseudo_bytes')) {
            $crypto_strong = false;
            $salt = openssl_random_pseudo_bytes($length / 2, $crypto_strong);
            if ($crypto_strong && $salt !== false) {
                return bin2hex($salt);
            }
        }
        
        // Método 3: Fallback usando múltiples fuentes
        return self::generateFallbackSalt($length);
    }
    
    private static function generateFallbackSalt($length) {
        $sources = [
            microtime(true),
            mt_rand(),
            uniqid('', true),
            $_SERVER['REQUEST_TIME_FLOAT'] ?? time(),
            memory_get_usage(),
            getmypid(),
        ];
        
        $base = hash('sha256', implode('|', $sources));
        
        // Expandir si necesitamos más longitud
        while (strlen($base) < $length) {
            $base .= hash('sha256', $base . microtime(true));
        }
        
        return substr($base, 0, $length);
    }
    
    private static function storeSaltToFile($salt) {
        try {
            // Preparar datos para almacenar
            $data = [
                'salt' => $salt,
                'created' => time(),
                'version' => '1.0'
            ];
            
            // Codificar de forma segura
            $encoded_data = self::encodeSaltData($data);
            
            // Escribir con permisos restrictivos
            $temp_file = self::$salt_file_path . '.tmp';
            
            if (file_put_contents($temp_file, $encoded_data, LOCK_EX) === false) {
                return false;
            }
            
            // Cambiar permisos antes de mover
            chmod($temp_file, 0600); // Solo lectura/escritura para el propietario
            
            // Mover archivo atómicamente
            if (!rename($temp_file, self::$salt_file_path)) {
                unlink($temp_file);
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to store salt: " . $e->getMessage(), 3, base_dir('writer/logs/salt_errors.log'));
            return false;
        }
    }
    
    private static function encodeSaltData($data) {
        // Serializar y codificar en base64
        $serialized = serialize($data);
        $encoded = base64_encode($serialized);
        
        // Añadir checksum para verificar integridad
        $checksum = hash('sha256', $encoded . 'SALT_CHECKSUM_KEY');
        
        return $encoded . '.' . $checksum;
    }
    
    private static function decodeSaltData($content) {
        // Separar contenido y checksum
        $parts = explode('.', $content);
        if (count($parts) !== 2) {
            return false;
        }
        
        list($encoded_data, $stored_checksum) = $parts;
        
        // Verificar checksum
        $calculated_checksum = hash('sha256', $encoded_data . 'SALT_CHECKSUM_KEY');
        if (!hash_equals($calculated_checksum, $stored_checksum)) {
            return false;
        }
        
        // Decodificar
        $serialized = base64_decode($encoded_data);
        if ($serialized === false) {
            return false;
        }
        
        // Deserializar de forma segura
        $data = @unserialize($serialized);
        if ($data === false) {
            return false;
        }
        
        // Validar estructura
        if (!self::validateSaltData($data)) {
            return false;
        }
        
        return $data;
    }
    
    private static function validateSaltData($data) {
        // Verificar que sea un array
        if (!is_array($data)) {
            return false;
        }
        
        // Verificar campos requeridos
        if (!isset($data['salt']) || !isset($data['created']) || !isset($data['version'])) {
            return false;
        }
        
        // Validar salt
        $salt = $data['salt'];
        if (!is_string($salt) || 
            strlen($salt) < self::$min_salt_length || 
            strlen($salt) > self::$max_salt_length) {
            return false;
        }
        
        // Validar que solo contenga caracteres hexadecimales
        if (!ctype_xdigit($salt)) {
            return false;
        }
        
        // Validar timestamp
        if (!is_numeric($data['created']) || $data['created'] < 0) {
            return false;
        }
        
        return true;
    }
    
    public static function regenerateSalt() {
        if (defined('APP_SALT')) {
            // No se puede redefinir una constante
            throw new Exception('Cannot regenerate salt: APP_SALT already defined');
        }
        
        $new_salt = self::generateSecureSalt();
        if (self::storeSaltToFile($new_salt)) {
            define('APP_SALT', $new_salt);
            return true;
        }
        
        return false;
    }
    
    public static function getSaltInfo() {
        $config_dir = base_dir('App\config');
        
        self::$salt_file_path = realpath($config_dir) . DIRECTORY_SEPARATOR . 'app_salt.dat';
        
        // Crear directorio si no existe
        if (!is_dir(dirname(self::$salt_file_path))) {
            mkdir(dirname(self::$salt_file_path), 0755, true);
        }
        
        if (!file_exists(self::$salt_file_path)) {
            return null;
        }
        
        $content = file_get_contents(self::$salt_file_path);
        $data = self::decodeSaltData($content);
        
        if ($data === false) {
            return null;
        }
        
        return [
            'created' => date('Y-m-d H:i:s', $data['created']),
            'version' => $data['version'],
            'file_path' => self::$salt_file_path,
            'salt_length' => strlen($data['salt'])
        ];
    }
}

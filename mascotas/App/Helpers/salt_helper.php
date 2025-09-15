<?php

if (!function_exists("get_salt_info")) {
    function get_salt_info() {
        return \App\Services\Auth\SecureSaltManager::getSaltInfo();
    }
}

if (!function_exists("is_salt_file_secure")) {
    function is_salt_file_secure() {
        $info = get_salt_info();
        if ($info === null) {
            return false;
        }
        
        $file_path = $info['file_path'];
        $perms = fileperms($file_path);
        
        // Verificar que solo el propietario tenga acceso
        return ($perms & 0777) === 0600;
    }
}

if (!function_exists("diagnose_salt_security")) {
    function diagnose_salt_security() {
        echo "<pre>";
        echo "=== DIAGNÓSTICO DE SEGURIDAD DEL SALT ===\n\n";
        
        echo "APP_SALT definido: " . (defined('APP_SALT') ? 'SÍ' : 'NO') . "\n";
        
        if (defined('APP_SALT')) {
            echo "Longitud del salt: " . strlen(APP_SALT) . " caracteres\n";
            echo "Formato válido: " . (ctype_xdigit(APP_SALT) ? 'SÍ' : 'NO') . "\n";
        }
        
        $info = get_salt_info();
        if ($info) {
            echo "Archivo de salt existe: SÍ\n";
            echo "Fecha de creación: " . $info['created'] . "\n";
            echo "Versión: " . $info['version'] . "\n";
            echo "Ruta del archivo: " . $info['file_path'] . "\n";
            echo "Permisos seguros: " . (is_salt_file_secure() ? 'SÍ' : 'NO') . "\n";
        } else {
            echo "Archivo de salt: NO ENCONTRADO O CORRUPTO\n";
        }
        
        echo "\n=== RECOMENDACIONES ===\n";
        if (!is_salt_file_secure()) {
            echo "- Corregir permisos del archivo de salt\n";
        }
        echo "- Añadir /config/app_salt.dat al .gitignore\n";
        echo "- Hacer backup del archivo de salt en lugar seguro\n";
        echo "</pre>";
    }
}
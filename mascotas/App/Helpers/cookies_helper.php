<?php

if (!function_exists("set_cookie")) {
	// Función para generar una cookie de forma segura
	function set_cookie(string $name, string $value, int $days = 30): void {
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
			throw new Exception("Error al momento de crear la cookie, el nombre posee caracteres especiales no permitidos.");
		}
	    $secure   = COOKIE_CONFIG['secure'];
	    $httponly = COOKIE_CONFIG['httponly'];
	    $samesite = COOKIE_CONFIG['samesite'];
	    $path 	  = COOKIE_CONFIG['path'];
	    $domain   = COOKIE_CONFIG['domain'];
	    $expire   = $days === 0 ? 0 : (time() + (86400 * $days)); // Tiempo de expiración en segundos
	    if (PHP_VERSION_ID < 70300) {
	        // Para versiones anteriores a PHP 7.3, SameSite se implementa manualmente
	        setcookie($name, $value, $expire, "$path; SameSite=$samesite", $domain, $secure, $httponly);
	    } else {
	        // Para PHP 7.3 y versiones posteriores
	        setcookie($name, $value, [
	            'expires' => $expire,
	            'path' => $path,
	            'domain' => $domain,
	            'secure' => $secure,
	            'httponly' => $httponly,
	            'samesite' => $samesite
	        ]);
	    }
	}
}

if (!function_exists("get_cookie")) {
	// Función para obtener una cookie de forma segura
	function get_cookie(string $name) {
	    try {
	        // Validar el nombre de la cookie
	        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
	            throw new Exception("Error al momento de obtener la cookie, el nombre posee caracteres especiales no permitidos.");
	        }
	        // Utilizar filter_input para obtener y sanitizar la cookie
	        if (PHP_VERSION_ID < 80000) {
	            // Para versiones de PHP anteriores a 8.0
	            $value = filter_input(INPUT_COOKIE, $name, FILTER_SANITIZE_STRING);
	        } else {
	            // Para PHP 8.0 y versiones posteriores
	            $value = filter_input(INPUT_COOKIE, $name, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	        }
	        // Verificar si la cookie está presente y no está vacía
	        if ($value === null || $value === false) {
	            return null;
	        }
	        return $value;
	    } catch (Exception $e) {
	        // Registrar el error (opcional)
	        cookie_log_error('Error al obtener la cookie: ' . $e->getMessage());
	        return null;
	    }
	}
}

if (!function_exists("delete_cookie")) {
	// Función para eliminar una cookie de forma segura
	function delete_cookie(string $name, bool $redelete = true) {
	    try {
	        // Validar el nombre de la cookie
	        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
	            throw new Exception("Error al momento de remover la cookie, el nombre posee caracteres especiales no permitidos.");
	        }
		    $secure   	= COOKIE_CONFIG['secure'];
		    $httponly 	= COOKIE_CONFIG['httponly'];
		    $path 	  	= COOKIE_CONFIG['path'];
		    $domain   	= COOKIE_CONFIG['domain'];
		    $retryDelay = COOKIE_CONFIG['delete_time_delay'];
	        setcookie($name, '', time() - 3600, $path, $domain, $secure, $httponly); // Expira la cookie
	        // Verificar si la cookie aún existe y reintentar una vez después de un retraso
	        if ($redelete === true && get_cookie($name)) {
	            sleep($retryDelay); // Esperar antes de volver a intentar
	            return delete_cookie($name, false);
	        }
	        // Comprobar si la cookie se ha eliminado
	        $isDeleted = !isset($_COOKIE[$name]);
	        // Registrar el resultado (opcional)
	        cookie_log_access('Intento de eliminación de cookie: ' . $name . ' - ' . ($isDeleted ? 'Éxito' : 'Fallo'));
	        return $isDeleted;
	    } catch (Exception $e) {
	        // Registrar el error (opcional)
	        cookie_log_error('Error al eliminar la cookie: ' . $e->getMessage());
	        return false;
	    }
	}
}

// Funciones opcionales para registrar accesos y errores
if (!function_exists("cookie_log_error")) {
	function cookie_log_error($message) {
	    error_log($message.PHP_EOL, 3, base_dir('writer/logs/cookie_errors.log'));
	}
}

if (!function_exists("cookie_log_access")) {
	function cookie_log_access($message) {
	    error_log($message.PHP_EOL, 3, base_dir('writer/logs/cookie_access.log'));
	}
}
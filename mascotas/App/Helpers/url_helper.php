<?php
if (!function_exists("validar_url")) {
	/**
	 * Valida que una URL tenga un formato correcto y cumpla con requisitos de seguridad
	 * 
	 * @param string $url    URL a validar
	 * @param array  $config Configuración de validación
	 * @return bool          TRUE si la URL es válida, FALSE en caso contrario
	 */
	function is_url(string $url, array $config = []): bool
	{
	    // Opciones configurables
	    $config = array_merge([
	        'dominio' => base_url(),     // Dominio permitido (si se usa 'solo_dominio')
	        'dns' => false,      // ¿Verificar existencia en DNS?
	        'ping' => false,     // ¿Verificar respuesta HTTP?
	        'solo_dominio' => false, // ¿Comparar con un dominio específico?
	        'tiempo' => 3,       // Timeout en segundos
	        'http_ok' => range(200, 299) // Códigos HTTP válidos
	    ], $config);

	    // Validación básica de formato
	    if (!filter_var($url, FILTER_VALIDATE_URL)) {
	        return false;
	    }

	    // Obtener componentes de la URL
	    $datos_url = parse_url($url);
	    
	    // Validaciones básicas (esquema https/http y host)
	    if (
	    	!isset($datos_url['scheme']) ||
	    	!in_array($datos_url['scheme'], ['http', 'https']) ||
	    	!isset($datos_url['host']) || empty($datos_url['host'])
	    ) { return false; }

	    // Verificación de DNS
	    if ($config['dns'] && !dns_get_record($datos_url['host'], DNS_A | DNS_AAAA)) { return false; }

	    // Verificación de dominio específico
	    if ($config['solo_dominio'] && !empty($config['dominio'])) {
	        // Convertir dominio a formato simple si viene como URL
	        $dominio_esperado = parse_url($config['dominio'], PHP_URL_HOST) ?: $config['dominio'];
	        if ($datos_url['host'] !== $dominio_esperado) { return false; }
	    }

	    // Verificación HTTP
	    if ($config['ping']) {
	        $ch = curl_init($url);
	        curl_setopt_array($ch, [
	            CURLOPT_NOBODY => true,
	            CURLOPT_TIMEOUT => $config['tiempo'],
	            CURLOPT_FOLLOWLOCATION => true,
	            CURLOPT_MAXREDIRS => 3,
	            CURLOPT_RETURNTRANSFER => true,
	            CURLOPT_SSL_VERIFYPEER => true,
	            CURLOPT_SSL_VERIFYHOST => 2
	        ]);
	        curl_exec($ch);
	        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	        curl_close($ch);
	        
	        if (!in_array($codigo, $config['http_ok'])) { return false; }
	    }

	    return true;
	}
}

if (!function_exists("sanitize_url")) {
	/**
	 * Sanitiza una URL para evitar inyecciones y caracteres maliciosos
	 * 
	 * @param string $url     URL a sanitizar
	 * @param bool   $encode  Aplicar urlencode a la URL (predeterminado: false)
	 * @return string         URL sanitizada o cadena vacía si no es válida
	 */
	function sanitize_url(string $url, bool $encode = false): string
	{
	    // Eliminar espacios en blanco al inicio y final
	    $url = trim($url);

	    // Eliminar caracteres invisibles y potencialmente maliciosos
	    $url = preg_replace('/[\x00-\x1F\x7F]/', '', $url);

	    // Validar formato básico de URL
	    if (!filter_var($url, FILTER_VALIDATE_URL)) {
	        // Intentar añadir http:// si no tiene esquema
	        if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
	            $url_con_esquema = 'http://' . $url;
	            // Valida si no es una URL válida ni siquiera con esquema
	            if (!filter_var($url_con_esquema, FILTER_VALIDATE_URL)) { return ''; }
	            $url = $url_con_esquema;
	        } else {
	            return ''; // No es una URL válida
	        }
	    }

	    // Eliminar cualquier fragmento de script o evento javascript
	    $url = preg_replace('/(javascript:|data:|vbscript:)/i', '', $url);

	    // Asegurar que el esquema sea http o https
	    $parsed = parse_url($url);
	    if (!isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), ['http', 'https'])) {
	        // Convertir a https si no tiene un esquema válido
	        $url = 'https://' . ltrim(preg_replace('~^(?:f|ht)tps?://~i', '', $url), '/');
	    }

	    // Aplicar urlencode si es necesario
	    if (false !== $encode) {
	        // Separar la URL en componentes para codificar solo las partes adecuadas
	        $parsed = parse_url($url);

	        // Reconstruir la URL con los componentes codificados apropiadamente
	        $scheme	  = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
	        $host 	  = isset($parsed['host']) ? $parsed['host'] : '';
	        $port 	  = isset($parsed['port']) ? ':' . $parsed['port'] : '';
	        $user 	  = isset($parsed['user']) ? $parsed['user'] : '';
	        $pass 	  = isset($parsed['pass']) ? ':' . $parsed['pass'] : '';
	        $auth 	  = ($user || $pass) ? "{$user}{$pass}@" : '';
	        $path 	  = isset($parsed['path']) ? $parsed['path'] : '';
	        $query 	  = isset($parsed['query']) ? '?' . $parsed['query'] : '';
	        $fragment = isset($parsed['fragment']) ? '#' . urlencode($parsed['fragment']) : '';

	        // Los caracteres especiales en path deben ser codificados
	        $path = implode('/', array_map('urlencode', explode('/', $path)));

	        return "{$scheme}{$auth}{$host}{$port}{$path}{$query}{$fragment}";
	    }

	    return $url;
	}
}

if (!function_exists("is_local_url")) {
	/**
	 * Verifica si una URL pertenece al dominio local
	 * 
	 * @param string $url     URL a verificar
	 * @param string $base    URL base del sistema (opcional)
	 * @return bool           TRUE si es local, FALSE en caso contrario
	 */
	function is_local_url(string $url, ?string $base = null): bool
	{
	    // Si no se proporciona una base, intentar detectarla
	    if (empty($base)) {
	        // Para proyectos que usan la función base_url()
	        if (function_exists('base_url')) {
	            $base = base_url();
	        } 
	        // Para otros proyectos, intentar construirla
	        else {
	            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
	            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
	            $base = $protocol . $host;
	        }
	    }
	    
	    // Sanitizar las URLs
	    $url = sanitize_url($url);
	    $base = sanitize_url($base);
	    
	    if (empty($url)) { return false; }
	    
	    // Obtener los hosts
	    $url_host = parse_url($url, PHP_URL_HOST);
	    $base_host = parse_url($base, PHP_URL_HOST);
	    
	    if (empty($url_host) || empty($base_host)) { return false; }
	    
	    // Verificar si el host de la URL coincide con el host base
	    if ($url_host === $base_host) { return true; }
	    
	    // Verificar subdominios (ejemplo.com coincide con sub.ejemplo.com)
	    $base_parts = explode('.', $base_host);
	    if (count($base_parts) >= 2) {
	        $domain_pattern = implode('\.', array_slice($base_parts, -2));
	        return (bool) preg_match('/\.' . preg_quote($domain_pattern, '/') . '$/', $url_host);
	    }
	    
	    return false;
	}
}

if (!function_exists("get_domain_from_url")) {
	/**
	 * Extrae el nombre de dominio de una URL
	 * 
	 * @param string $url       URL de la que se extraerá el dominio
	 * @param bool   $www       Incluir 'www.' si existe (predeterminado: false)
	 * @param bool   $subdominio Incluir subdominios (predeterminado: false)
	 * @return string           Dominio extraído o cadena vacía si no es válido
	 */
	function get_domain_from_url(string $url, bool $www = false, bool $subdominio = false): string
	{
	    // Sanitizar la URL
	    $url = sanitize_url($url);

	    if (empty($url)) { return ''; }

	    // Obtener el host
	    $host = parse_url($url, PHP_URL_HOST);

	    if (empty($host)) { return ''; }

	    // Si no queremos incluir 'www.', lo eliminamos
	    if (!$www && strpos($host, 'www.') === 0) {
	        $host = substr($host, 4);
	    }

	    // Si no queremos subdominios, extraemos solo el dominio principal
	    if (!$subdominio) {
	        // Extraer componentes del host
	        $host_parts = explode('.', $host);
	        $count = count($host_parts);

	        // Obtener el dominio principal + TLD
	        if ($count >= 2) {
	            // Casos especiales como co.uk, com.mx, etc.
	            $tld = $host_parts[$count - 1];
	            $sld = $host_parts[$count - 2];

	            // Lista de dominios de segundo nivel conocidos
	            $sld_tlds = ['co', 'com', 'net', 'org', 'ac', 'edu', 'gov', 'mil'];

	            return ($count > 2 && in_array($sld, $sld_tlds))
	            	? $host_parts[$count - 3]."{$sld}.{$tld}"
	            	: "{$sld}.{$tld}";
	        }
	    }
	   
	    return $host;
	}
}

if (!function_exists("build_absolute_url")) {
	/**
	 * Construye una URL absoluta a partir de una URL relativa
	 *
	 * @param string $relative_url URL relativa 
	 * @param string $base_url     URL base (opcional)
	 * @return string              URL absoluta
	 */
	function build_absolute_url($relative_url, $base_url = null)
	{
	    // Si la URL ya es absoluta, devolverla
	    if (preg_match('~^(?:f|ht)tps?://~i', $relative_url)) { return $relative_url; }

	    // Si no se proporciona una base, intentar detectarla
	    if (empty($base_url)) {
	        if (function_exists('base_url')) {
	            $base_url = rtrim(base_url(), '/');
	        } else {
	            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
	            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
	            $base_url = $protocol . $host;
	        }
	    } else {
	        $base_url = rtrim($base_url, '/');
	    }

	    // Si la URL relativa comienza con /, unirla directamente al dominio
	    if (strpos($relative_url, '/') === 0) {
	        // Extraer solo el dominio de la URL base
	        $parsed_base = parse_url($base_url);
	        $domain = $parsed_base['scheme'] . '://' . $parsed_base['host'];
	        if (isset($parsed_base['port'])) {
	            $domain .= ':' . $parsed_base['port'];
	        }
	        return $domain . $relative_url;
	    }

	    // De lo contrario, simplemente unir la base con la URL relativa
	    return $base_url . '/' . ltrim($relative_url, '/');
	}
}

if (!function_exists("current_url")) {
	function current_url($with_query_string = true) {
	    // Detectar si es HTTPS
	    $is_https = (
	        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
	        $_SERVER['SERVER_PORT'] == 443 ||
	        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
	        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
	    );
	    
	    $protocol = $is_https ? 'https://' : 'http://';
	    
	    // Obtener el host
	    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
	    
	    // Obtener la URI
	    $uri = $_SERVER['REQUEST_URI'] ?? '';
	    
	    // Si no queremos query string, removerlo
	    if (!$with_query_string && strpos($uri, '?') !== false) {
	        $uri = substr($uri, 0, strpos($uri, '?'));
	    }
	    
	    return $protocol . $host . $uri;
	}
}

if (!function_exists("current_url_no_query")) {
	// Variantes adicionales útiles
	function current_url_no_query() {
	    return current_url(false);
	}
}

if (!function_exists("get_current_domain")) {
	function get_current_domain() {
	    $is_https = (
	        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
	        $_SERVER['SERVER_PORT'] == 443 ||
	        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
	    );
	    
	    $protocol = $is_https ? 'https://' : 'http://';
	    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
	    
	    return $protocol . $host;
	}
}

if (!function_exists("is_local_environment")) {
	function is_local_environment() {
	    $local_ips 	 = [
	    	'localhost',
	    	'127.0.0.1',
	    	'::1',
	    ];

	    $server_name = $_SERVER['SERVER_NAME'] ?? '';
	    $remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';

	    return strtolower($_ENV['environment']) !== "production" && (
	    	in_array($server_name, $local_ips) || 
        	in_array($remote_addr, $local_ips) ||
        	strpos($server_name, '.local') !== false ||
        	strpos($server_name, 'localhost') !== false
        );
	}
}

if (!function_exists("get_base_url")) {
	function get_base_url() {
	    $protocol = (
	        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
	        $_SERVER['SERVER_PORT'] == 443
	    ) ? 'https://' : 'http://';
	    
	    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
	    
	    // Obtener el directorio base del script
	    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
	    $base_path = ($script_dir === '/' || $script_dir === '\\') ? '' : $script_dir;
	    
	    return $protocol . $host . $base_path;
	}
}

if (!function_exists("debug_current_request")) {
	// Función para debugging (solo en desarrollo)
	function debug_current_request() {
	    if (!is_local_environment()) { return; }
	    
	    echo "<pre>";
	    echo "Current URL: " . current_url() . "\n";
	    echo "Current URL (no query): " . current_url_no_query() . "\n";
	    echo "Current Domain: " . get_current_domain() . "\n";
	    echo "Base URL: " . get_base_url() . "\n";
	    echo "APP_SALT defined: " . (defined('APP_SALT') ? 'YES' : 'NO') . "\n";
	    if (defined('APP_SALT')) {
	        echo "APP_SALT length: " . strlen(APP_SALT) . "\n";
	    }
	    echo "</pre>";
	}
}
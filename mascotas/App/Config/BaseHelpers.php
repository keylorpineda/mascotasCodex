<?php
if (!function_exists("model")) {
	function model(string $nombre_model, ?string $nombre_db = 'default')
	{
        return App\Core\ModelLoader::load($nombre_model, $nombre_db);
    }
}

if (!function_exists("helper")) {
	function helper($names): void
	{
		$namespace = "App\Helpers".DIRECTORY_SEPARATOR;
		if (!is_array($names)) {
			if (strpos($names, ",")) {
				$names = explode(",", str_replace(" ", "", $names));
			} else {
				$names = [$names];
			}
		}
		$includes = [];
		foreach ($names as $name) {
			if (strpos($name, '_helper') === false) { $name .= '_helper'; }
			if (in_array($name, $includes, true)) { continue; }
			$filename = $namespace.$name.".php";
			$fullPath = base_dir($filename);
			if (file_exists($fullPath)) {
				$includes[] = $filename;
				continue;
			}
			throw new \Exception("Archivo helper '{$name}' no encontrado.", 1);
		}
		foreach ($includes as $path) {
			require_once base_dir($path);
		}
	}
}

if (!function_exists("service")) {
	function service(string $nombre_service)
	{
		$namespace = "App\Services".DIRECTORY_SEPARATOR;
		if (!strstr($nombre_service, $namespace)) {
			$nombre_service = $namespace.$nombre_service;
		}
		if (!class_exists($nombre_service)) {
			return null;
		}
		return new $nombre_service();
	}
}

if (!function_exists("is_ajax")) {
	function is_ajax(): bool
	{
		return (
        	(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        	(isset($_SERVER['HTTP_ACCEPT']) 		  && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false 	   ) ||
        	(isset($_SERVER['CONTENT_TYPE']) 		  && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false    )
    	);
	}
}

if (!function_exists("is_cli")) {
	function is_cli(): bool
	{
	    return (php_sapi_name() === 'cli');
	}
}

if (!function_exists("session")) {
	function session(?string $name = null)
	{
		$ses = (new \App\Config\Session());
		if (!is_null($name)) { return $ses->get($name); }
		return $ses;
	}
}

if (!function_exists('base_dir')) {
    function base_dir(?string $ruta = ''): string
    {
        // Validar que BASE_DIR esté definida
        if (!defined('BASE_DIR')) {
            throw new RuntimeException("La constante BASE_DIR no está definida.");
        }

        // Asegurarse de que BASE_DIR tenga un formato adecuado
        $baseDir = rtrim(BASE_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Normalizar y limpiar la ruta proporcionada
        $ruta = trim($ruta ?? '', '/\\'); // Eliminar barras iniciales y finales
        $ruta = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ruta); // Convertir a separadores de sistema

        // Escapar la ruta para mayor seguridad
        $ruta = htmlspecialchars($ruta, ENT_QUOTES, 'UTF-8');

        // Retornar la ruta completa
        return $baseDir . $ruta;
    }
}

if (!function_exists('base_url')) {
    function base_url(?string $ruta = ""): string
    {
        // Obtén la base URL desde el entorno o una constante configurada
        $base_url = $_ENV['base_url'] ?? (defined('BASE_URL') ? BASE_URL : '/');
        
        // Validar que la base URL sea válida
        if (!filter_var($base_url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException("La base URL '{$base_url}' no es válida.");
        }
        
        // Asegúrate de que la base URL termine con una barra
        $base_url = rtrim($base_url, '/') . '/';
        
        // Limpia y normaliza la ruta proporcionada
        $ruta = ltrim($ruta, '/');
        $ruta = htmlspecialchars($ruta, ENT_QUOTES, 'UTF-8'); // Escapar la ruta para mayor seguridad
        
        // Retorna la URL completa
        return "{$base_url}{$ruta}";
    }
}

if (!function_exists('log_message')) {
    function log_message(string $level, string $message, array $context = []): void
    {
        static $logger = null;

        if ($logger === null) {
            $logPath = null;

            try {
                $logPath = base_dir('logs');

                if (!is_dir($logPath)) {
                    @mkdir($logPath, 0755, true);
                }
            } catch (\Throwable $e) {
                $logPath = null;
            }

            $logger = new \App\Core\Logger\SimpleLogger($logPath);
        }

        $level = strtolower($level);

        if (method_exists($logger, $level)) {
            $logger->{$level}($message, $context);
            return;
        }

        $logger->log($level, $message, $context);
    }
}

if (!function_exists('validar_origen_peticion')) {
	function validar_origen_peticion(array $origins = []): void
	{
		if (strtolower($_ENV['environment']) !== "production") {
		    $origins[] = 'http://localhost';
		    $origins[] = 'http://127.0.0.1';
		}

		// Configuración completa pero optimizada
		$validator = new \App\Core\RequestOriginValidator([
		    'allowedOrigins' => $origins,
		    'allowNoOrigin' => false,
		    'logLevel' => 'warning',
		    // 'logFile' => 'writer/logs/security.log', // Registrar intentos en archivo
		    'securityHeaders' => true // Añadir cabeceras de seguridad básicas
		]);

		// Validar y bloquear en una sola línea
		$validator->blockIfInvalid("Acceso restringido.");
	}
}

if (!function_exists("vd")) {
	function vd(...$params): void
	{
		if (!is_ajax() && !is_cli()) { echo "<pre>"; }
		var_dump(...$params);
		// print_r(...$params);
		if (!is_ajax() && !is_cli()) { echo "</pre>"; }
	}
}

if (!function_exists("vds")) {
	function vds(...$params): void
	{
		vd(...$params);
		die();
	}
}
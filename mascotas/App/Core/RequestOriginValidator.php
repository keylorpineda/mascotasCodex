<?php
namespace App\Core;
/**
 * Clase para validar el origen de peticiones HTTP
 * 
 * Esta clase proporciona métodos para validar si una petición HTTP proviene de un origen permitido,
 * implementando varias capas de seguridad y opciones de configuración.
 */
class RequestOriginValidator
{
    /**
     * Lista de orígenes permitidos
     * @var array
     */
    private $allowedOrigins = [];
    
    /**
     * Indica si se permite el acceso sin origen (por ejemplo, navegación directa)
     * @var bool
     */
    private $allowNoOrigin = false;
    
    /**
     * Nivel de registro para eventos de seguridad
     * @var string
     */
    private $logLevel = 'warning';
    
    /**
     * Ruta al archivo de registro
     * @var string|null
     */
    private $logFile = "writer/logs/security.log";
    
    /**
     * Constructor
     * 
     * @param array $config Configuración del validador
     */
    public function __construct(array $config = []) {
        // Configurar orígenes permitidos
        if (isset($config['allowedOrigins'])) {
            $this->allowedOrigins = $config['allowedOrigins'];
        } elseif (defined('SV_NAME')) {
            $this->allowedOrigins = [SV_NAME];
        }
        
        // Otras opciones de configuración
        $this->allowNoOrigin = $config['allowNoOrigin'] ?? false;
        $this->logLevel      = $config['logLevel']      ?? 'warning';
        $this->logFile     ??= $config['logFile'];
    }

    /**
     * Valida el origen de la petición actual
     * 
     * @return bool True si el origen es válido, False en caso contrario
     */
    public function isValidOrigin(): bool {
        $origin  = $_SERVER['HTTP_ORIGIN']  ?? null;
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        if (is_null($origin) && isset($referer)) {
            $parsedUrl = parse_url($referer);
            $origin = "{$parsedUrl['scheme']}://{$parsedUrl['host']}";
        }

        // Si no hay origen y está permitido navegar sin origen
        if (!$origin) {
            // Validar que el host del servidor esté en allowedOrigins
            $hostOrigin = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
            return $this->isAllowedOrigin($hostOrigin) || $this->allowNoOrigin;
        }

        // Verificar si el origen está en la lista de permitidos
        return $this->isAllowedOrigin($origin);
    }
    
    /**
     * Comprueba si un origen específico está permitido
     * 
     * @param string $origin El origen a comprobar
     * @return bool True si el origen está permitido, False en caso contrario
     */
    private function isAllowedOrigin(string $origin): bool {
        // Normalizar el origen eliminando posibles barras inclinadas al final
        $origin = rtrim($origin, '/');

        foreach ($this->allowedOrigins as $allowedOrigin) {
            // Si es un patrón comodín (por ejemplo, *.example.com)
            if (strpos($allowedOrigin, '*') !== false) {
                $pattern = str_replace('*', '.*', preg_quote($allowedOrigin, '/'));
                if (preg_match('/^' . $pattern . '$/', $origin)) {
                    return true;
                }
            } 
            // Comparación directa
            elseif ($origin === $allowedOrigin) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Bloquea la petición si el origen no es válido
     * 
     * @param string $message Mensaje de error personalizado
     * @param int $statusCode Código de estado HTTP para responder
     * @return void
     */
    public function blockIfInvalid(string $message = "Acceso denegado", int $statusCode = 403): void {
        if (!$this->isValidOrigin()) {
            $this->logSecurityEvent();
            $this->respondWithError($message, $statusCode);
        }
    }
    
    /**
     * Añade un origen a la lista de permitidos
     * 
     * @param string $origin Origen a añadir
     * @return self
     */
    public function addAllowedOrigin(string $origin): self {
        if (!in_array($origin, $this->allowedOrigins)) {
            $this->allowedOrigins[] = $origin;
        }
        return $this;
    }
    
    /**
     * Establece si se permite el acceso sin origen
     * 
     * @param bool $allow True para permitir, False para denegar
     * @return self
     */
    public function setAllowNoOrigin(bool $allow): self {
        $this->allowNoOrigin = $allow;
        return $this;
    }
    
    /**
     * Registra un evento de seguridad
     * 
     * @return void
     */
    private function logSecurityEvent(): void {
        if ($this->logLevel === 'none') { return; }
        
        $origin     = $_SERVER['HTTP_ORIGIN']     ?? $_SERVER['HTTP_REFERER'] ?? 'No origin';
        $ip         = $_SERVER['REMOTE_ADDR']     ?? 'Unknown IP';
        $userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? 'No User-Agent';
        $requestUri = $_SERVER['REQUEST_URI']     ?? 'Unknown URI';

        $logMessage = sprintf(
            "[%s] [ORIGIN-VALIDATION-FAILED] Origin: %s, IP: %s, URI: %s, User-Agent: %s",
            date('Y-m-d H:i:s'),
            $origin,
            $ip,
            $requestUri,
            $userAgent
        );

        if (!is_file($this->logFile)) {
            mkdir(dirname($this->logFile), 0700, true);
        }

        if ($this->logFile) {
            error_log($logMessage . PHP_EOL, 3, $this->logFile);
        } else {
            error_log($logMessage);
        }
    }
    
    /**
     * Responde con un error y termina la ejecución
     * 
     * @param string $message Mensaje de error
     * @param int $statusCode Código de estado HTTP
     * @return void
     */
    private function respondWithError(string $message, int $statusCode): void {
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Establecer cabeceras de seguridad adicionales
        header("HTTP/1.1 {$statusCode} {$message}");
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("Content-Type: application/json");
        
        // Responder con JSON estructurado
        echo json_encode([
            'error' => [
                'code' => $statusCode,
                'message' => $message
            ]
        ]);
        
        exit;
    }
}
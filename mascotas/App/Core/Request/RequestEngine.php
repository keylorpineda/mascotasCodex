<?php

declare(strict_types=1);

namespace App\Core\Request;

use App\Core\Request\Exception\InvalidHttpMethodException;
use App\Core\Request\Exception\MalformedUriException;
use App\Core\Request\Exception\JsonDecodeException;
use App\Core\Request\RequestInterface;

/**
 * Class RequestEngine
 *
 * Una implementación robusta de manejo de peticiones HTTP con funcionalidad extendida,
 * tipado estricto, sanitización de entrada, carga lazy y manejo integral de errores.
 */
class RequestEngine implements RequestInterface
{
    private const VALID_METHODS = [
        'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD', 'TRACE'
    ];

    private string $method;
    private string $uri;
    private array $get;
    private array $post;
    private array $segments;
    private array $headers;
    private array $files;
    private array $cookies;
    private ?string $body = null;
    private mixed $jsonData = null;
    private bool $jsonParsed = false;
    private array $parsedInputData = [];
    private bool $inputDataParsed = false;

    /**
     * Constructor del Request
     * @throws InvalidHttpMethodException
     * @throws MalformedUriException
     */
    public function __construct()
    {
        $this->method = $this->determineMethod();
        $this->validateMethod($this->method);

        $this->uri = $this->determineUri();
        $this->validateUri($this->uri);

        $this->headers = $this->loadHeaders();

        // Sanitizar entradas globales
        $this->get = $this->sanitizeInput($_GET);
        $this->post = $this ->sanitizeInput($_POST);
         $this->files = $this->sanitizeFiles($_FILES);
        $this->cookies = $this->sanitizeInput($_COOKIE);

        // Inicializar segmentos como array vacío (lazy loading)
        $this->segments = [];
    }

    /**
     * Determinar método HTTP, permitiendo override via campo _method
     */
    private function determineMethod(): string
    {
        $methodOverride = filter_input(INPUT_POST, '_method', FILTER_SANITIZE_SPECIAL_CHARS);
        
        if ($methodOverride) {
            return strtoupper($methodOverride);
        }

        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Validar método HTTP contra lista de métodos permitidos
     * @throws InvalidHttpMethodException
     */
    private function validateMethod(string $method): void
    {
        if (!in_array($method, self::VALID_METHODS, true)) {
            throw new InvalidHttpMethodException($method);
        }
    }

    /**
     * Determinar la URI de la petición
     */
    private function determineUri(): string
    {
        $urlParam = filter_input(INPUT_GET, 'url', FILTER_UNSAFE_RAW);
        if (isset($_GET['url'])) {
            unset($_GET['url']);
        }
        
        if ($urlParam !== null) {
            return trim($urlParam, '/');
        }

        $requestUri  = $_SERVER['REQUEST_URI']  ?? '';
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        
        // Remover query string si existe
        if ($queryString && str_contains($requestUri, '?')) {
            $requestUri = strstr($requestUri, '?', true);
        }

        // Remover ruta base del script si es necesario
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath   = dirname($scriptName);
        
        if ($basePath !== '/' && str_starts_with($requestUri, $basePath)) {
            $requestUri = substr($requestUri, strlen($basePath));
        }

        return trim($requestUri, '/');
    }

    /**
     * Validar formato de URI
     * @throws MalformedUriException
     */
    private function validateUri(string $uri): void
    {
        if (preg_match('#^(https?://|//)#i', $uri)) {
            throw new MalformedUriException($uri);
        }

        // Validar caracteres peligrosos
        if (preg_match('/[<>"\']/', $uri)) {
            throw new MalformedUriException($uri);
        }
    }

    /**
     * Cargar headers HTTP con respaldo
     */
    private function loadHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders() ?: [];
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * Sanitizar entrada de datos
     */
    private function sanitizeInput(array $input): array
    {
        $sanitized = [];
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                // Normalizar espacios en blanco pero preservar saltos de línea
                $value = trim($value);
                // Remover solo caracteres potencialmente peligrosos
                $value = str_replace(['<script', '</script>', 'javascript:', 'onload=', 'onerror='], '', $value);
                // Escapar entidades HTML básicas
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
                $sanitized[$key] = $value;
            }
            // $sanitized[$key] = is_array($value) 
            //     ? $this->sanitizeInput($value) 
            //     : trim(filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS));
        }
        return $sanitized;
    }

    /**
     * Sanitizar archivos subidos
     */
    private function sanitizeFiles(array $files): array
    {
        $sanitized = [];
        
        foreach ($files as $key => $file) {
            if (is_array($file)) {
                // Caso 1: Archivo individual
                if (isset($file['name']) && !is_array($file['name'])) {
                    $sanitized[$key] = [
                        'name' => filter_var($file['name'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
                        'type' => filter_var($file['type'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
                        'tmp_name' => $file['tmp_name']    ?? '',
                        'error' => (int)($file['error']    ?? UPLOAD_ERR_NO_FILE),
                        'size' => (int)($file['size']      ?? 0)
                    ];
                }
                // Caso 2: Array de archivos (COMPROBANTES[])
                elseif (isset($file['name']) && is_array($file['name'])) {
                    $sanitized[$key] = [];
                    $count = count($file['name']);
                    for ($i = 0; $i < $count; $i++) {
                        $sanitized[$key][$i] = [
                            'name' => filter_var($file['name'][$i] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
                            'type' => filter_var($file['type'][$i] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
                            'tmp_name' => $file['tmp_name'][$i]    ?? '',
                            'error' => (int)($file['error'][$i]    ?? UPLOAD_ERR_NO_FILE),
                            'size' => (int)($file['size'][$i]      ?? 0)
                        ];
                    }
                }
            }
        }
        
        return $sanitized;
    }
    /**
     * Parsear datos desde php://input
     * Lee TODOS los datos desde php://input para métodos que no sean GET o POST
     */
    private function parseInputData(): array
    {
        if ($this->inputDataParsed) {
            return $this->parsedInputData;
        }

        $this->inputDataParsed = true;
        $body = $this->getBody();
        
        if (empty($body)) {
            $this->parsedInputData = [];
            return $this->parsedInputData;
        }

        $contentType = $this->getHeader('content-type') ?? $this->getHeader('Content-Type') ?? '';
        
        // Verificar si es JSON
        if (str_contains($contentType, 'application/json')) {
            try {
                $jsonData = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                $this->parsedInputData = is_array($jsonData) ? $jsonData : [];
            } catch (\JsonException $e) {
                $this->parsedInputData = [];
            }
        }
        // Verificar si es form-urlencoded
        elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($body, $this->parsedInputData);
            $this->parsedInputData = $this->sanitizeInput($this->parsedInputData);
        }
        // Para multipart/form-data, parsear manualmente desde php://input
        elseif (str_contains($contentType, 'multipart/form-data')) {
            $this->parsedInputData = $this->parseMultipartFormData($body, $contentType);
        }
        // Para otros tipos, intentar parsear como query string
        else {
            parse_str($body, $this->parsedInputData);
            $this->parsedInputData = $this->sanitizeInput($this->parsedInputData);
        }

        return $this->parsedInputData;
    }

    /**
     * Parsear datos multipart/form-data manualmente desde php://input
     */
    private function parseMultipartFormData(string $body, string $contentType): array
    {
        $data = [];
        
        // Extraer boundary del Content-Type
        if (!preg_match('/boundary=(.+)$/', $contentType, $matches)) {
            return $data;
        }
        
        $boundary = '--' . $matches[1];
        $parts = explode($boundary, $body);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part) || $part === '--') {
                continue;
            }
            
            // Separar headers del contenido
            if (strpos($part, "\r\n\r\n") !== false) {
                [$headers, $content] = explode("\r\n\r\n", $part, 2);
            } elseif (strpos($part, "\n\n") !== false) {
                [$headers, $content] = explode("\n\n", $part, 2);
            } else {
                continue;
            }
            
            // Extraer el nombre del campo
            if (preg_match('/name="([^"]+)"/', $headers, $nameMatch)) {
                $fieldName = $nameMatch[1];
                
                // Verificar si es un archivo
                if (strpos($headers, 'filename=') !== false) {
                    // Es un archivo - por ahora solo guardamos el nombre del campo
                    // Los archivos reales se manejan mejor con $_FILES en POST
                    $data[$fieldName] = trim($content);
                } else {
                    // Es un campo regular
                    $data[$fieldName] = trim($content);
                }
            }
        }
        
        return $this->sanitizeInput($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): string
    {
        return $this->uri === '' ? '/' : '/' . $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function getSegments(?int $index = null): array|string|null
    {
        if (empty($this->segments)) {
            $this->segments = array_values(array_filter(explode('/', $this->uri), fn($seg) => $seg !== ''));
        }

        if ($index !== null) {
            return $this->segments[$index] ?? null;
        }

        return $this->segments;
    }

    /**
     * {@inheritdoc}
     */
    public function getGet(?string $key = null): mixed
    {
        return $this->getBodyFor("GET", $key);
    }

    /**
     * {@inheritdoc}
     */
    public function getPost(?string $key = null): mixed
    {
        return $this->getBodyFor("POST", $key);
    }

    /**
     * {@inheritdoc}
     */
    public function getPut(?string $key = null): mixed
    {
        return $this->getBodyFor("PUT", $key);
    }

    /**
     * {@inheritdoc}
     */
    public function getDelete(?string $key = null): mixed
    {
        return $this->getBodyFor("DELETE", $key);
    }

    /**
     * Obtiene el cuerpo del request (GET, POST, PUT, DELETE) si coincide con el método HTTP actual.
     *
     * Este método permite acceder de forma segura al contenido de los parámetros enviados
     * según el método HTTP especificado. Para métodos como PUT, DELETE, PATCH, etc.,
     * los datos se leen desde php://input y se parsean según el Content-Type.
     *
     * @param string $method Método HTTP a validar (ej. 'GET', 'POST', 'PUT', 'DELETE').
     * @param string|null $key Clave opcional para obtener un valor específico dentro del cuerpo.
     * @return mixed Valor de la clave específica o el arreglo completo del cuerpo. Retorna [] si el método no coincide.
     */
    private function getBodyFor(string $method, ?string $key = null): mixed
    {
        if (strtoupper($this->getMethod()) !== strtoupper($method)) {
            return [];
        }

        $body = match (strtoupper($method)) {
            'GET' => $this->get,
            'POST' => $this->post,
            'PUT', 'PATCH', 'DELETE' => $this->parseInputData(),
            default => [],
        };

        return $key === null
            ? $body
            : ($body[$key] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles(?string $key = null): array|null
    {
        if ($key === null) {
            return $this->files;
        }

        return $this->files[$key] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        if ($this->body === null) {
            $this->body = file_get_contents('php://input') ?: '';
        }

        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function getJson(): mixed
    {
        if (!$this->jsonParsed) {
            $body = $this->getBody();
            
            if (empty($body)) {
                $this->jsonData = null;
            } else {
                try {
                    $this->jsonData = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new JsonDecodeException($e->getMessage());
                }
            }
            
            $this->jsonParsed = true;
        }

        return $this->jsonData;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookie(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->cookies;
        }

        return $this->cookies[$key] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function isAjax(): bool
    {
        return is_ajax();
    }

    /**
     * {@inheritdoc}
     */
    public function isSecure(): bool
    {
        return 
            !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ||
            $_SERVER['SERVER_PORT'] == 443 ||
            strtolower($this->getHeader('X-Forwarded-Proto') ?? '') === 'https';
    }

    /**
     * {@inheritdoc}
     */
    public function getClientIp(): string
    {
        $headers = [
            'X-Forwarded-For',
            'X-Real-IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            $ip = $this->getHeader($header) ?? ($_SERVER[$header] ?? null);
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getUserAgent(): ?string
    {
        return $this->getHeader('User-Agent') ?? $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Establecer el método HTTP manualmente (interfaz fluida)
     * @throws InvalidHttpMethodException
     */
    public function setMethod(string $method): self
    {
        $method = strtoupper($method);
        $this->validateMethod($method);
        $this->method = $method;

        return $this;
    }

    /**
     * Verificar si existe un parámetro GET específico
     */
    public function hasGet(string $key): bool
    {
        return isset($this->get[$key]);
    }

    /**
     * Verificar si existe un parámetro POST específico
     */
    public function hasPost(string $key): bool
    {
        return isset($this->post[$key]);
    }

    /**
     * Verificar si existe un parámetro PUT específico
     */
    public function hasPut(string $key): bool
    {
        if (!$this->isMethod('PUT')) {
            return false;
        }
        $data = $this->parseInputData();
        return isset($data[$key]);
    }

    /**
     * Verificar si existe un parámetro DELETE específico
     */
    public function hasDelete(string $key): bool
    {
        if (!$this->isMethod('DELETE')) {
            return false;
        }
        $data = $this->parseInputData();
        return isset($data[$key]);
    }

    /**
     * Verificar si existe un header específico
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    /**
     * Verificar si existe un archivo específico
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Obtener el método HTTP original antes de cualquier override
     */
    public function getOriginalMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Obtener el protocolo usado (HTTP/HTTPS)
     */
    public function getProtocol(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Obtener la URL completa
     */
    public function getFullUrl(): string
    {
        $protocol = $this->getProtocol();
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $uri = $this->getUri();
        
        return "{$protocol}://{$host}{$uri}";
    }

    /**
     * Obtener todos los parámetros del request actual independientemente del método
     */
    public function getAllParams(): array
    {
        return match ($this->getMethod()) {
            'GET' => $this->get,
            'POST' => $this->post,
            'PUT', 'PATCH', 'DELETE' => $this->parseInputData(),
            default => [],
        };
    }

    /**
     * Obtener un parámetro específico del request actual independientemente del método
     */
    public function getParam(string $key): mixed
    {
        $params = $this->getAllParams();
        return $params[$key] ?? null;
    }

    /**
     * Verificar si existe un parámetro específico en el request actual
     */
    public function hasParam(string $key): bool
    {
        $params = $this->getAllParams();
        return isset($params[$key]);
    }
}
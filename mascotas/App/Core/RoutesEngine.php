<?php

namespace App\Core;

use App\Core\Request\RequestEngine;
use App\Core\Routes\Exception\RouteNotFoundException;
use App\Core\Routes\Exception\ControllerNotFoundException;
use App\Core\Routes\Exception\MethodNotFoundException;
use App\Core\Routes\Exception\InvalidHandlerException;
use App\Core\Routes\Exception\MiddlewareException;
use App\Core\Logger\SimpleLogger;
use InvalidArgumentException;
use Exception;

class RoutesEngine extends RequestEngine
{
    private array $routes = [];
    private array $compiledRoutes = []; // Cache de rutas compiladas
    private string $defaultController = '';
    private string $defaultNamespace = '';
    private string $defaultMethod = 'index';
    private string $controller = '';
    private string $method = '';
    private array $params = [];
    
    // Nuevas propiedades para funcionalidad extendida
    private array $globalMiddleware = [];
    private array $routeMiddleware = [];
    private array $routeGroups = [];
    private ?SimpleLogger $logger = null;
    private bool $debugMode = false;

    public function __construct()
    {
        parent::__construct();
        
        // Inicializar logger simple
        $this->logger = new SimpleLogger();
        
        // Detectar modo debug
        $this->debugMode = $_ENV["environment"] !== "production" || (defined('DEBUG') && DEBUG);
        
        $this->log('RoutesEngine initialized', 'info');
    }

    /**
     * Configurar el namespace por defecto
     */
    public function setDefaultNamespace(string $namespace): static
    {
        if (empty($namespace)) {
            throw new InvalidArgumentException('Namespace cannot be empty');
        }
        
        $this->defaultNamespace = rtrim(strip_tags($namespace), '\\') . '\\';
        $this->log("Default namespace set to: {$this->defaultNamespace}");
        return $this;
    }

    /**
     * Configurar el controlador por defecto
     */
    public function setDefaultController(string $controller): static
    {
        if (empty($controller)) {
            throw new InvalidArgumentException('Controller cannot be empty');
        }
        
        $this->defaultController = $controller;
        $this->log("Default controller set to: {$controller}");
        return $this;
    }

    /**
     * Configurar el método por defecto
     */
    public function setDefaultMethod(string $method): static
    {
        if (empty($method)) {
            throw new InvalidArgumentException('Method cannot be empty');
        }
        
        $this->defaultMethod = $method;
        $this->log("Default method set to: {$method}");
        return $this;
    }

    /**
     * Configurar logger personalizado
     */
    public function setLogger(SimpleLogger $logger): static
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Habilitar/deshabilitar modo debug
     */
    public function setDebugMode(bool $debug): static
    {
        $this->debugMode = $debug;
        return $this;
    }

    /**
     * Registrar ruta GET
     */
    public function get(string $route, string|callable $handler): static
    {
        $this->addRoute('GET', $route, $handler);
        return $this;
    }

    /**
     * Registrar ruta POST
     */
    public function post(string $route, string|callable $handler): static
    {
        $this->addRoute('POST', $route, $handler);
        return $this;
    }

    /**
     * Registrar ruta PUT
     */
    public function put(string $route, string|callable $handler): static
    {
        $this->addRoute('PUT', $route, $handler);
        return $this;
    }

    /**
     * Registrar ruta DELETE
     */
    public function delete(string $route, string|callable $handler): static
    {
        $this->addRoute('DELETE', $route, $handler);
        return $this;
    }

    /**
     * Registrar ruta PATCH
     */
    public function patch(string $route, string|callable $handler): static
    {
        $this->addRoute('PATCH', $route, $handler);
        return $this;
    }

    /**
     * Registrar ruta OPTIONS
     */
    public function options(string $route, string|callable $handler): static
    {
        $this->addRoute('OPTIONS', $route, $handler);
        return $this;
    }

    /**
     * Agregar middleware global
     */
    public function addGlobalMiddleware(callable $middleware): static
    {
        $this->globalMiddleware[] = $middleware;
        $this->log('Global middleware added');
        return $this;
    }

    /**
     * Agregar middleware a una ruta específica
     */
    public function addRouteMiddleware(string $method, string $route, callable $middleware): static
    {
        $normalizedRoute = $this->normalizeRoute($route);
        $this->routeMiddleware[strtoupper($method)][$normalizedRoute][] = $middleware;
        $this->log("Route middleware added for: {$method} {$normalizedRoute}");
        return $this;
    }

    /**
     * Crear un grupo de rutas con prefijo común
     */
    public function group(string $prefix, callable $callback): static
    {
        $originalGroups = $this->routeGroups;
        $this->routeGroups[] = trim($prefix, '/');
        
        try {
            $callback($this);
        } finally {
            $this->routeGroups = $originalGroups;
        }
        
        return $this;
    }

    /**
     * Agregar ruta con validación mejorada
     */
    private function addRoute(string $method, string $route, string|callable $handler): void
    {
        // Validación de entrada
        if (empty($route)) {
            throw new InvalidArgumentException('Route cannot be empty');
        }
        
        if (is_string($handler) && empty($handler)) {
            throw new InvalidArgumentException('Handler cannot be empty');
        }
        
        if (is_string($handler) && !str_contains($handler, '::') && !is_callable($handler)) {
            throw new InvalidHandlerException("Invalid handler format: {$handler}. Expected format: 'Controller::method' or callable");
        }

        // Aplicar prefijos de grupo
        $fullRoute = $this->buildFullRoute($route);
        $normalizedRoute = $this->normalizeRoute($fullRoute);
        
        $this->routes[$method][$normalizedRoute] = $handler;
        
        // Pre-compilar la ruta para mejor rendimiento
        $this->compileRoute($method, $normalizedRoute);
        
        $this->log("Route registered: {$method} {$normalizedRoute}");
    }

    /**
     * Construir ruta completa con prefijos de grupo
     */
    private function buildFullRoute(string $route): string
    {
        if (empty($this->routeGroups)) {
            return $route;
        }
        
        $prefix = implode('/', $this->routeGroups);
        return $prefix . '/' . ltrim($route, '/');
    }

    /**
     * Pre-compilar ruta para mejor rendimiento
     */
    private function compileRoute(string $method, string $route): void
    {
        $pattern = preg_replace('#\{([^}]+)\}#', '([^/]+)', $route);
        $pattern = "#^" . $pattern . "$#";
        $this->compiledRoutes[$method][$route] = $pattern;
    }

    /**
     * Despachar la ruta
     */
    public function dispatch(): void
    {
        try {
            $method = $this->getMethod();
            $uri = $this->normalizeRoute($this->getUri());

            $this->log("Dispatching: {$method} {$uri}");

            // Ejecutar middleware global
            $this->executeGlobalMiddleware($method, $uri);

            $response = $this->findAndExecuteRoute($method, $uri);

            $this->sendResponse($response);
        } catch (RouteNotFoundException $e) {
            $this->handleHttpError(404, $e->getMessage());
        } catch (ControllerNotFoundException | MethodNotFoundException $e) {
            $this->handleHttpError(500, $e->getMessage());
        } catch (MiddlewareException $e) {
            $this->handleHttpError(403, $e->getMessage());
        } catch (Exception $e) {
            $this->log("Unexpected error in dispatch: " . $e->getMessage(), 'error');
            $this->handleHttpError(500, 'Internal Server Error');
        }
    }

    /**
     * Ejecutar middleware global
     */
    private function executeGlobalMiddleware(string $method, string $uri): void
    {
        foreach ($this->globalMiddleware as $middleware) {
            $result = $middleware($method, $uri, $this);
            if ($result === false) {
                throw new MiddlewareException('Global middleware blocked request');
            }
        }
    }

    /**
     * Buscar y ejecutar ruta
     */
    private function findAndExecuteRoute(string $method, string $uri): mixed
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $routePattern => $handler) {
            if ($this->matchRoute($routePattern, $uri)) {
                // Ejecutar middleware específico de la ruta
                $this->executeRouteMiddleware($method, $routePattern);
                
                return $this->callHandler($handler);
            }
        }

        throw new RouteNotFoundException("Route not found: {$method} {$uri}");
    }

    /**
     * Ejecutar middleware específico de ruta
     */
    private function executeRouteMiddleware(string $method, string $route): void
    {
        if (!isset($this->routeMiddleware[$method][$route])) {
            return;
        }

        foreach ($this->routeMiddleware[$method][$route] as $middleware) {
            $result = $middleware($method, $route, $this);
            if ($result === false) {
                throw new MiddlewareException('Route middleware blocked request');
            }
        }
    }

    /**
     * Enviar respuesta al cliente
     */
    private function sendResponse(mixed $response): void
    {
        // Mantener compatibilidad con is_ajax()
        if ($this->isAjax()) {
            if (!is_string($response)) {
                $response = json_encode($response);
            }
            header('Content-Type: application/json');
        }

        echo $response;
    }

    /**
     * Verificar si la ruta coincide (optimizado)
     */
    private function matchRoute(string $routePattern, string $uri): bool
    {
        // Usar ruta pre-compilada si existe
        if (isset($this->compiledRoutes[$this->getMethod()][$routePattern])) {
            $pattern = $this->compiledRoutes[$this->getMethod()][$routePattern];
        } else {
            $pattern = preg_replace('#\{([^}]+)\}#', '([^/]+)', $routePattern);
            $pattern = "#^" . $pattern . "$#";
        }

        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); // Eliminar el match completo
            $this->params = $matches;
            return true;
        }

        return false;
    }

    /**
     * Llamar al handler con mejor manejo de errores
     */
    private function callHandler(string|callable $handler): mixed
    {
        try {
            // Si es un callable (closure)
            if (is_callable($handler) && !is_string($handler)) {
                $this->log("Calling closure handler");
                return call_user_func_array($handler, $this->params);
            }

            // Si es string (formato Controller::method)
            if (!str_contains($handler, '::')) {
                throw new InvalidHandlerException("Invalid handler format: {$handler}");
            }

            [$controllerName, $methodName] = explode('::', $handler);
            
            // Manejar controladores con namespace anidado (ej: Nomina\Constancias)
            $controllerClass = $this->buildControllerClass($controllerName);

            $this->log("Calling handler: {$controllerClass}::{$methodName}");

            // Verificar si la clase existe
            if (!class_exists($controllerClass)) {
                throw new ControllerNotFoundException("Controller {$controllerClass} not found.");
            }

            // Crear instancia del controlador
            $controller = new $controllerClass();

            // Verificar si el método existe
            if (!method_exists($controller, $methodName)) {
                throw new MethodNotFoundException("Method {$methodName} not found in controller {$controllerClass}.");
            }

            return call_user_func_array([$controller, $methodName], $this->params);
        } catch (ControllerNotFoundException | MethodNotFoundException | InvalidHandlerException $e) {
            $this->log("Error calling handler: " . $e->getMessage(), 'error');
            throw $e;
        } catch (Exception $e) {
            $this->log("Unexpected error calling handler: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Construir nombre completo de la clase del controlador
     */
    private function buildControllerClass(string $controllerName): string
    {
        // Si ya incluye el namespace completo, usarlo tal cual
        if (str_starts_with($controllerName, 'App\\')) {
            return $controllerName;
        }
        
        // Si contiene barras (ej: Nomina\Constancias), mantener la estructura
        if (str_contains($controllerName, '\\')) {
            return $this->defaultNamespace . $controllerName;
        }
        
        // Controlador simple
        return $this->defaultNamespace . $controllerName;
    }

    /**
     * Manejar errores HTTP
     */
    private function handleHttpError(int $code, string $message): void
    {
        http_response_code($code);
        
        $this->log("HTTP {$code}: {$message}", 'error');
        
        $response = match($code) {
            404 => $this->handle404($message),
            500 => $this->handle500($message),
            403 => $this->handle403($message),
            default => $this->handleGenericError($code, $message)
        };
        
        echo $response;
    }

    /**
     * Manejar error 404 (mantiene compatibilidad)
     */
    private function handle404(): string
    {
        header("HTTP/1.0 404 Not Found");
        
        return $this->buildErrorResponse(404, $this->debugMode ? $message : 'Page Not Found');
    }

    /**
     * Manejar error 500
     */
    private function handle500(string $message): string
    {
        header("HTTP/1.0 500 Internal Server Error");
        
        return $this->buildErrorResponse(500, $this->debugMode ? $message : 'Internal Server Error');
    }

    /**
     * Manejar error 403
     */
    private function handle403(string $message): string
    {
        header("HTTP/1.0 403 Forbidden");
        
        return $this->buildErrorResponse(403, $message);
    }

    /**
     * Manejar error genérico
     */
    private function handleGenericError(int $code, string $message): string
    {
        return $this->buildErrorResponse($code, $message);
    }

    /**
     * Construir respuesta de error
     */
    private function buildErrorResponse(int $code, string $message): string
    {
        if ($this->isAjax()) {
            return json_encode([
                'error' => true,
                'code' => $code,
                'message' => $message
            ]);
        }
        
        if (function_exists('view')) {
            return view('errors/error', ['type' => $code,'message' => $message]);
        }
        
        return "
            <!DOCTYPE html>
            <html>
                <head>
                    <title>Error {$code}</title>
                    <style>
                        body { font-family: Arial, sans-serif; text-align: center; margin-top: 100px; }
                        .error { background: #f8f8f8; padding: 20px; border-radius: 5px; display: inline-block; }
                    </style>
                </head>
                <body>
                    <div class='error'>
                        <h1>Error {$code}</h1>
                        <p>{$message}</p>
                    </div>
                </body>
            </html>
        ";
    }

    /**
     * Normalizar ruta (mejorado)
     */
    private function normalizeRoute(string $route): string
    {
        if (empty($route)) {
            return '/';
        }
        
        $route = parse_url($route, PHP_URL_PATH) ?? '/';
        $normalized = '/' . trim($route, '/');
        
        // Asegurar que siempre termine correctamente
        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }

    /**
     * Sistema de logging
     */
    private function log(string $message, string $level = 'info'): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message);
        }
    }

    /**
     * Obtener parámetros de la ruta actual
     */
    public function getRouteParams(): array
    {
        return $this->params;
    }

    /**
     * Obtener un parámetro específico de la ruta
     */
    public function getRouteParam(int $index, mixed $default = null): mixed
    {
        return $this->params[$index] ?? $default;
    }

    /**
     * Obtener parámetro por nombre (para rutas nombradas)
     */
    public function getNamedParam(string $name, mixed $default = null): mixed
    {
        // Esta funcionalidad se puede expandir para rutas nombradas
        return $default;
    }

    /**
     * Limpiar cache de rutas compiladas
     */
    public function clearCompiledRoutes(): static
    {
        $this->compiledRoutes = [];
        $this->log('Compiled routes cache cleared');
        return $this;
    }

    /**
     * Obtener todas las rutas registradas
     */
    public function getRegisteredRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Obtener estadísticas del router
     */
    public function getStats(): array
    {
        $totalRoutes = 0;
        foreach ($this->routes as $method => $routes) {
            $totalRoutes += count($routes);
        }
        
        return [
            'total_routes' => $totalRoutes,
            'compiled_routes' => count($this->compiledRoutes),
            'global_middleware' => count($this->globalMiddleware),
            'methods' => array_keys($this->routes)
        ];
    }
}
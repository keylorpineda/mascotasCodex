<?php
namespace App\Core;

class ViewEngine {
    // Instancia singleton para evitar problemas de estado global
    private static ?ViewEngine $instance = null;
    
    // Almacena secciones definidas
    private array $sections = [];
    
    // Pila de secciones activas para soportar anidación
    private array $sectionStack = [];
    
    // Almacena el layout actual
    private ?string $currentLayout = null;
    
    // Configuración
    private array $config = [
        'views_path' => 'App/Views',
        'layouts_path' => 'App/Views/layouts',
        'auto_escape' => true,
        'cache_enabled' => false,
        'cache_path' => 'storage/cache/views',
        'cache_lifetime' => 3600 // 1 hora en segundos
    ];

    private function __construct() {}

    /**
     * Obtiene la instancia singleton
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Configura el motor de vistas
     */
    public function configure(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Inicia una sección
     * @param string $name Nombre de la sección
     */
    public function section(string $name): void
    {
        ob_start();
        $this->sectionStack[] = $name;
        if (!isset($this->sections[$name])) {
            $this->sections[$name] = '';
        }
    }

    /**
     * Finaliza y guarda una sección
     */
    public function endSection(): void
    {
        if (empty($this->sectionStack)) {
            throw new \Exception("No hay secciones activas para cerrar");
        }

        $content = ob_get_clean();
        $sectionName = array_pop($this->sectionStack);
        
        // Concatenar contenido si la sección ya existe (para @parent)
        $this->sections[$sectionName] .= $content;
    }

    /**
     * Renderiza el contenido de una sección
     * @param string $name Nombre de la sección
     * @param string $default Contenido por defecto si la sección está vacía
     */
    public function renderSectionEscaped(string $name, string $default = ''): void
    {
        $content = $this->sections[$name] ?? $default;
        
        if ($this->config['auto_escape']) {
            $content = $this->escapeContent($content);
        }
        
        echo $content;
    }

    /**
     * Renderiza contenido sin escape (raw)
     * @param string $name Nombre de la sección
     * @param string $default Contenido por defecto
     */
    public function renderSection(string $name, string $default = ''): void
    {
        echo $this->sections[$name] ?? $default;
    }

    /**
     * Establece el layout para la vista
     * @param string $layoutName Nombre del layout
     */
    public function layout(string $layoutName): void
    {
        $this->currentLayout = $layoutName;
    }

    /**
     * Renderiza una vista con soporte de layout
     * @param string $name Nombre de la vista
     * @param array $data Datos para pasar a la vista
     * @param bool $return Indica si se debe devolver o imprimir
     * @return string|null
     */
    public function render(string $name, array $data = [], bool $return = false): ?string
    {
        // Crear nuevo contexto para evitar contaminación
        $context = $this->createRenderContext();
        
        try {
            return $this->renderInContext($name, $data, $return, $context);
        } finally {
            // Limpiar contexto
            $this->cleanupContext($context);
        }
    }

    /**
     * Crea un contexto limpio para renderizado
     */
    private function createRenderContext(): array
    {
        return [
            'sections' => $this->sections,
            'sectionStack' => $this->sectionStack,
            'currentLayout' => $this->currentLayout
        ];
    }

    /**
     * Limpia el contexto después del renderizado
     */
    private function cleanupContext(array $context): void
    {
        // Limpiar buffers pendientes
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Renderiza en un contexto específico
     */
    private function renderInContext(string $name, array $data, bool $return, array $context): ?string
    {
        // Verificar cache si está habilitado
        if ($this->config['cache_enabled']) {
            $cacheKey = $this->generateCacheKey($name, $data);
            $cachedContent = $this->getCachedView($cacheKey, $name);
            
            if ($cachedContent !== null) {
                if ($return) {
                    return $cachedContent;
                }
                echo $cachedContent;
                return null;
            }
        }

        // Restablecer estado para este renderizado
        $this->sections = [];
        $this->sectionStack = [];
        $this->currentLayout = null;

        // Preparar datos de la vista de forma segura
        $viewData = $this->prepareViewData($data);

        // Capturar contenido de la vista
        ob_start();
        $viewPath = $this->getViewPath($name);
        
        if (!file_exists($viewPath)) {
            ob_end_clean();
            throw new \Exception("Vista no encontrada: {$name}", 404);
        }

        try {
            // Usar closure para aislar scope
            $renderView = function() use ($viewPath, $viewData) {
                extract($viewData, EXTR_SKIP);
                require $viewPath;
            };
            
            $renderView();
            $content = ob_get_clean();

            // Si hay un layout definido, renderizarlo
            if ($this->currentLayout !== null) {
                $finalContent = $this->renderWithLayout($content, true);
            } else {
                $finalContent = $content;
            }

            // Guardar en cache si está habilitado
            if ($this->config['cache_enabled']) {
                $this->cacheView($cacheKey, $finalContent);
            }

            // Si no hay layout, devolver o imprimir el contenido de la vista
            if ($return) {
                return $finalContent;
            }
            
            echo $finalContent;
            return null;

        } catch (\Throwable $e) {
            // Limpiar buffer en caso de error
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }
    }

    /**
     * Prepara los datos de la vista de forma segura
     */
    private function prepareViewData(array $data): array
    {
        $safeLists = ['_GET', '_POST', '_SESSION', '_COOKIE', '_SERVER', '_ENV'];
        $filtered = [];
        
        foreach ($data as $key => $value) {
            // Filtrar variables potencialmente peligrosas
            if (!in_array($key, $safeLists) && !str_starts_with($key, '_')) {
                $filtered[$key] = $value;
            }
        }
        
        return $filtered;
    }

    /**
     * Renderiza con layout
     */
    private function renderWithLayout(string $content, bool $return): ?string
    {
        $layoutPath = $this->getLayoutPath($this->currentLayout);
        
        if (!file_exists($layoutPath)) {
            throw new \Exception("Layout no encontrado: " . $this->currentLayout, 404);
        }

        // Establecer el contenido principal como una sección
        $this->sections['content'] = $content;

        ob_start();
        
        $renderLayout = function() use ($layoutPath) {
            require $layoutPath;
        };
        
        $renderLayout();
        $finalContent = ob_get_clean();

        if ($return) {
            return $finalContent;
        }
        
        echo $finalContent;
        return null;
    }

    /**
     * Obtiene la ruta de una vista
     */
    private function getViewPath(string $name): string
    {
        $basePath = $this->config['views_path'];
        return base_dir("{$basePath}/{$name}.php");
    }

    /**
     * Obtiene la ruta de un layout
     */
    private function getLayoutPath(string $layoutName): string
    {
        $basePath = $this->config['layouts_path'];
        return base_dir("{$basePath}/{$layoutName}.php");
    }

    /**
     * Escapa contenido para prevenir XSS
     */
    private function escapeContent(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Método helper para escapar manualmente
     */
    public function escape(string $content): string
    {
        return $this->escapeContent($content);
    }

    /**
     * Limpia el estado (útil para testing)
     */
    public function reset(): void
    {
        $this->sections = [];
        $this->sectionStack = [];
        $this->currentLayout = null;
    }

    /**
     * Genera una clave de cache para una vista
     */
    private function generateCacheKey(string $name, array $data): string
    {
        // Incluir layout en la clave si existe
        $layoutKey = $this->currentLayout ?? 'no-layout';
        
        // Crear hash basado en nombre, layout y datos
        $dataHash = md5(serialize($data));
        
        return md5("{$name}_{$layoutKey}_{$dataHash}");
    }

    /**
     * Obtiene una vista desde cache
     */
    private function getCachedView(string $cacheKey, string $viewName): ?string
    {
        $cacheFile = $this->getCacheFilePath($cacheKey);
        
        if (!file_exists($cacheFile)) {
            return null;
        }

        // Verificar si el cache ha expirado
        $cacheTime = filemtime($cacheFile);
        $currentTime = time();
        
        if (($currentTime - $cacheTime) > $this->config['cache_lifetime']) {
            unlink($cacheFile);
            return null;
        }

        // Verificar si los archivos fuente han cambiado
        $viewPath = $this->getViewPath($viewName);
        $viewTime = filemtime($viewPath);
        
        if ($viewTime > $cacheTime) {
            unlink($cacheFile);
            return null;
        }

        // Verificar layout si existe
        if ($this->currentLayout !== null) {
            $layoutPath = $this->getLayoutPath($this->currentLayout);
            if (file_exists($layoutPath)) {
                $layoutTime = filemtime($layoutPath);
                if ($layoutTime > $cacheTime) {
                    unlink($cacheFile);
                    return null;
                }
            }
        }

        return file_get_contents($cacheFile);
    }

    /**
     * Guarda una vista en cache
     */
    private function cacheView(string $cacheKey, string $content): void
    {
        $cacheFile = $this->getCacheFilePath($cacheKey);
        $cacheDir = dirname($cacheFile);
        
        // Crear directorio de cache si no existe
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents($cacheFile, $content, LOCK_EX);
    }

    /**
     * Obtiene la ruta del archivo de cache
     */
    private function getCacheFilePath(string $cacheKey): string
    {
        $cachePath = $this->config['cache_path'];
        return base_dir("{$cachePath}/{$cacheKey}.cache");
    }

    /**
     * Limpia todo el cache de vistas
     */
    public function clearCache(): void
    {
        $cachePath = base_dir($this->config['cache_path']);
        
        if (is_dir($cachePath)) {
            $files = glob("{$cachePath}/*.cache");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Métodos estáticos para compatibilidad hacia atrás
     */
    public static function _section(string $name): void
    {
        self::getInstance()->section($name);
    }

    public static function _endSection(): void
    {
        self::getInstance()->endSection();
    }

    public static function _renderSectionEscaped(string $name, string $default = ''): void
    {
        self::getInstance()->renderSectionEscaped($name, $default);
    }

    public static function _renderSection(string $name, string $default = ''): void
    {
        self::getInstance()->renderSection($name, $default);
    }

    public static function _layout(string $layoutName): void
    {
        self::getInstance()->layout($layoutName);
    }

    public static function _render(string $name, array $data = [], bool $return = false): ?string
    {
        return self::getInstance()->render($name, $data, $return);
    }

    public static function _escape(string $content): string
    {
        return self::getInstance()->escape($content);
    }

    public static function _clearCache(): void
    {
        self::getInstance()->clearCache();
    }
}
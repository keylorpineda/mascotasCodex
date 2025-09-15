<?php

if (!function_exists("view")) {
    /**
     * Renderiza una vista
     * @param string $name Nombre de la vista
     * @param array $data Datos para pasar a la vista
     * @param bool $return Si debe devolver el contenido o imprimirlo
     * @return string|null
     */
    function view(string $name, array $data = [], bool $return = true): ?string {
        return App\Core\ViewEngine::_render($name, $data, $return);
    }
}

if (!function_exists("section")) {
    /**
     * Inicia una sección
     * @param string $name Nombre de la sección
     */
    function section(string $name): void {
        App\Core\ViewEngine::_section($name);
    }
}

if (!function_exists("endSection")) {
    /**
     * Finaliza una sección
     */
    function endSection(): void {
        App\Core\ViewEngine::_endSection();
    }
}

if (!function_exists("layout")) {
    /**
     * Establece el layout para la vista
     * @param string $layoutName Nombre del layout
     */
    function layout(string $layoutName): void {
        App\Core\ViewEngine::_layout($layoutName);
    }
}

if (!function_exists("renderSection")) {
    /**
     * Renderiza el contenido de una sección (con escape automático)
     * @param string $name Nombre de la sección
     * @param string $default Contenido por defecto
     */
    function renderSection(string $name, string $default = ''): void {
        App\Core\ViewEngine::_renderSection($name, $default);
    }
}

if (!function_exists("renderSectionRaw")) {
    /**
     * Renderiza el contenido de una sección sin escape
     * @param string $name Nombre de la sección
     * @param string $default Contenido por defecto
     */
    function renderSectionRaw(string $name, string $default = ''): void {
        App\Core\ViewEngine::_renderSectionRaw($name, $default);
    }
}

if (!function_exists("e")) {
    /**
     * Escapa contenido para prevenir XSS
     * @param string $content Contenido a escapar
     * @return string
     */
    function e(string $content): string {
        return App\Core\ViewEngine::_escape($content);
    }
}

if (!function_exists("configure_views")) {
    /**
     * Configura el motor de vistas
     * @param array $config Configuración
     */
    function configure_views(array $config): void {
        App\Core\ViewEngine::_getInstance()->configure($config);
    }
}

if (!function_exists("clear_view_cache")) {
    /**
     * Limpia el cache de vistas
     */
    function clear_view_cache(): void {
        App\Core\ViewEngine::_clearCache();
    }
}
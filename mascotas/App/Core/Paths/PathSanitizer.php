<?php

declare(strict_types=1);

/**
 * Clase para sanitización segura de rutas de archivos
 * Compatible con PHP 8+
 */
class PathSanitizer 
{
    // Caracteres prohibidos en nombres de archivo (Windows y Unix)
    private const FORBIDDEN_CHARS = ['<', '>', ':', '"', '|', '?', '*', "\0"];
    
    // Nombres reservados en Windows
    private const RESERVED_NAMES = [
        'CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 
        'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 
        'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'
    ];
    
    // Límites de longitud
    private const MAX_PATH_LENGTH = 260;  // Windows limit
    private const MAX_FILENAME_LENGTH = 255;  // Most filesystems
    
    /**
     * Sanitiza una ruta de archivo de forma segura
     * 
     * @param string $path Ruta a sanitizar
     * @param bool $allowAbsolute Si permitir rutas absolutas
     * @param string|null $basePath Ruta base para validar que la ruta esté dentro
     * @return string Ruta sanitizada
     * @throws InvalidArgumentException Si la ruta es inválida
     */
    public static function sanitize(
        string $path, 
        bool $allowAbsolute = true, 
        ?string $basePath = null
    ): string {
        // Validación inicial
        if (empty(trim($path))) {
            throw new InvalidArgumentException('La ruta no puede estar vacía');
        }
        
        // Normalizar separadores de directorio
        $path = self::normalizeSeparators($path);
        
        // Limpiar caracteres de control y espacios en blanco problemáticos
        $path = self::cleanControlChars($path);
        
        // Validar longitud total
        if (strlen($path) > self::MAX_PATH_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('La ruta excede el límite de %d caracteres', self::MAX_PATH_LENGTH)
            );
        }
        
        // Resolver referencias relativas (./ y ../)
        $path = self::resolveRelativeReferences($path);
        
        // Validar cada componente de la ruta
        $path = self::validatePathComponents($path);
        
        // Validar ruta absoluta si no está permitida
        if (!$allowAbsolute && self::isAbsolutePath($path)) {
            throw new InvalidArgumentException('No se permiten rutas absolutas');
        }
        
        // Validar que la ruta esté dentro del directorio base
        if ($basePath !== null) {
            self::validateWithinBasePath($path, $basePath);
        }
        
        // Prevenir ataques de path traversal
        self::preventPathTraversal($path);
        
        return $path;
    }
    
    /**
     * Normaliza los separadores de directorio
     */
    private static function normalizeSeparators(string $path): string 
    {
        // Reemplazar todos los separadores por el separador del sistema
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        
        // Eliminar separadores duplicados
        $path = preg_replace(
            '#' . preg_quote(DIRECTORY_SEPARATOR, '#') . '+#', 
            DIRECTORY_SEPARATOR, 
            $path
        );
        
        return $path;
    }
    
    /**
     * Limpia caracteres de control y espacios problemáticos
     */
    private static function cleanControlChars(string $path): string 
    {
        // Eliminar caracteres de control (0x00-0x1F y 0x7F-0x9F)
        $path = preg_replace('/[\x00-\x1F\x7F-\x9F]/', '', $path);
        
        // Eliminar espacios al inicio y final
        $path = trim($path);
        
        // Eliminar espacios y puntos al final de cada componente (problema en Windows)
        $components = explode(DIRECTORY_SEPARATOR, $path);
        $components = array_map(function($component) {
            return rtrim($component, ' .');
        }, $components);
        
        return implode(DIRECTORY_SEPARATOR, $components);
    }
    
    /**
     * Resuelve referencias relativas como ./ y ../
     */
    private static function resolveRelativeReferences(string $path): string 
    {
        $isAbsolute = self::isAbsolutePath($path);
        $components = explode(DIRECTORY_SEPARATOR, $path);
        $resolved = [];
        
        foreach ($components as $component) {
            if ($component === '' || $component === '.') {
                continue;
            }
            
            if ($component === '..') {
                if (!empty($resolved)) {
                    array_pop($resolved);
                } elseif (!$isAbsolute) {
                    // Para rutas relativas, mantener .. si estamos al principio
                    $resolved[] = $component;
                }
            } else {
                $resolved[] = $component;
            }
        }
        
        $result = implode(DIRECTORY_SEPARATOR, $resolved);
        
        // Preservar el separador inicial para rutas absolutas
        if ($isAbsolute && !empty($result)) {
            // En Windows, preservar la letra de unidad
            if (PHP_OS_FAMILY === 'Windows' && preg_match('/^[A-Za-z]:/', $path)) {
                return $result;
            }
            // En Unix, agregar el separador inicial
            $result = DIRECTORY_SEPARATOR . $result;
        }
        
        return $result;
    }
    
    /**
     * Valida cada componente de la ruta
     */
    private static function validatePathComponents(string $path): string 
    {
        $components = explode(DIRECTORY_SEPARATOR, $path);
        
        foreach ($components as $component) {
            if ($component === '') continue;
            
            // Verificar longitud del nombre del archivo
            if (strlen($component) > self::MAX_FILENAME_LENGTH) {
                throw new InvalidArgumentException(
                    sprintf('El nombre "%s" excede el límite de %d caracteres', 
                    $component, self::MAX_FILENAME_LENGTH)
                );
            }
            
            // Verificar caracteres prohibidos
            foreach (self::FORBIDDEN_CHARS as $char) {
                if (strpos($component, $char) !== false) {
                    throw new InvalidArgumentException(
                        sprintf('El carácter "%s" no está permitido en nombres de archivo', $char)
                    );
                }
            }
            
            // Verificar nombres reservados (Windows)
            if (PHP_OS_FAMILY === 'Windows') {
                $nameWithoutExt = pathinfo($component, PATHINFO_FILENAME);
                if (in_array(strtoupper($nameWithoutExt), self::RESERVED_NAMES, true)) {
                    throw new InvalidArgumentException(
                        sprintf('El nombre "%s" es reservado del sistema', $component)
                    );
                }
            }
            
            // Verificar que no termine con punto o espacio (Windows)
            if (PHP_OS_FAMILY === 'Windows' && preg_match('/[\s.]$/', $component)) {
                throw new InvalidArgumentException(
                    'Los nombres de archivo no pueden terminar con punto o espacio'
                );
            }
        }
        
        return $path;
    }
    
    /**
     * Verifica si una ruta es absoluta
     */
    private static function isAbsolutePath(string $path): bool 
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return preg_match('/^[A-Za-z]:/', $path) === 1;
        }
        
        return str_starts_with($path, DIRECTORY_SEPARATOR);
    }
    
    /**
     * Valida que la ruta esté dentro del directorio base
     */
    private static function validateWithinBasePath(string $path, string $basePath): void 
    {
        $basePath = self::sanitize($basePath, true);
        $realBase = realpath($basePath);
        
        if ($realBase === false) {
            throw new InvalidArgumentException('El directorio base no existe');
        }
        
        // Si la ruta es relativa, combinarla con la base
        if (!self::isAbsolutePath($path)) {
            $fullPath = $realBase . DIRECTORY_SEPARATOR . $path;
        } else {
            $fullPath = $path;
        }
        
        $realPath = realpath(dirname($fullPath));
        
        if ($realPath === false) {
            // Si el directorio no existe, verificar manualmente
            $realPath = self::normalizeSeparators($fullPath);
        }
        
        if (!str_starts_with($realPath, $realBase)) {
            throw new InvalidArgumentException(
                'La ruta está fuera del directorio base permitido'
            );
        }
    }
    
    /**
     * Previene ataques de path traversal
     */
    private static function preventPathTraversal(string $path): void 
    {
        // Verificar secuencias sospechosas después de la sanitización
        $dangerous = ['..', '~', '$'];
        
        foreach ($dangerous as $pattern) {
            if (strpos($path, $pattern) !== false) {
                throw new InvalidArgumentException(
                    sprintf('Patrón sospechoso detectado: %s', $pattern)
                );
            }
        }
        
        // Verificar URLs y protocolos
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $path)) {
            throw new InvalidArgumentException('No se permiten URLs o protocolos');
        }
    }
    
    /**
     * Función de conveniencia para uso rápido
     */
    public static function quick(string $path): string 
    {
        return self::sanitize($path, true);
    }
    
    /**
     * Sanitiza una ruta relativa de forma segura
     */
    public static function relative(string $path, string $basePath): string 
    {
        return self::sanitize($path, false, $basePath);
    }
    
    /**
     * Valida si una ruta es segura sin sanitizar
     */
    public static function isSecure(string $path): bool 
    {
        try {
            self::sanitize($path);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}

// Función global para uso directo
function sanitize_path(string $path, bool $allowAbsolute = true, ?string $basePath = null): string 
{
    return PathSanitizer::sanitize($path, $allowAbsolute, $basePath);
}

// Ejemplos de uso
try {
    // Ejemplo con tu ruta
    $ruta = "C:\Users\Javi\AppData\Local\Temp/boletas_686d58e09079e0.69066596/BOLETAS_NO_DEFINIDO_2025_06_2025-07-08_11-44-00.zip";
    
    echo "Ruta original: " . $ruta . "\n";
    echo "Ruta sanitizada: " . PathSanitizer::quick($ruta) . "\n\n";
    
    // Otros ejemplos
    $ejemplos = [
        "../../../etc/passwd",
        "archivo<>.txt",
        "C:/temp//doble//separador.txt",
        "archivo con espacios.pdf",
        "CON.txt",  // Nombre reservado en Windows
        "archivo.txt ",  // Espacio al final
    ];
    
    foreach ($ejemplos as $ejemplo) {
        echo "Probando: " . $ejemplo . "\n";
        try {
            $resultado = PathSanitizer::quick($ejemplo);
            echo "✓ Resultado: " . $resultado . "\n";
        } catch (InvalidArgumentException $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
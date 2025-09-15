<?php
namespace App\Core;

class ModelLoader {
    /**
     * Carga un modelo de manera segura y flexible
     * 
     * @param string $modelName Nombre del modelo
     * @param string|null $connectionName Nombre de la conexión de base de datos
     * @return object|null Instancia del modelo o null si no se puede cargar
     * @throws \Exception En caso de errores críticos
     */
    public static function load(string $modelName, ?string $connectionName = 'default'): ?object
    {
        // Namespace base para modelos
        $baseNamespace = "App\\Models\\";
        
        // Preparar el nombre completo del modelo
        $fullModelName = str_contains($modelName, $baseNamespace) 
            ? $modelName 
            : $baseNamespace . $modelName;
        // Validaciones de seguridad
        if (empty($modelName)) {
            throw new \InvalidArgumentException("El nombre del modelo no puede estar vacío");
        }
        
        // Verificar existencia de la clase
        if (!class_exists($fullModelName)) {
            // Log de error opcional
            error_log(
                "Modelo no encontrado: {$fullModelName}".PHP_EOL, 3, base_dir("wirter/logs/modelLoader.log")
            );
            return null;
        }
        
        // Verificar que la clase sea instanciable
        $reflection = new \ReflectionClass($fullModelName);
        if (!$reflection->isInstantiable()) {
            throw new \Exception("El modelo {$fullModelName} no puede ser instanciado");
        }
        
        try {
            // Intentar crear la instancia
            return new $fullModelName($connectionName);
        } catch (\Throwable $e) {
            // Manejo de errores detallado
            error_log(
                "Error al instanciar modelo {$fullModelName}: " . $e->getMessage().PHP_EOL, 3, base_dir("wirter/logs/modelLoader.log")
            );
            throw new \Exception("No se pudo crear la instancia del modelo", 500, $e);
        }
    }
}
<?php
namespace App\Core;

class ModelLoader {
    /**
     * Carga un modelo de manera segura y flexible
     * @param string $modelName  p.ej. 'Mascotas\\MascotasModel' o 'App\\Models\\Mascotas\\MascotasModel'
     * @param string|null $connectionName  nombre de la conexión ('.env'), por defecto 'default'
     */
    public static function load(string $modelName, ?string $connectionName = 'default'): object
    {
        $baseNamespace = "App\\Models\\";
        if ($modelName === '') {
            throw new \InvalidArgumentException("El nombre del modelo no puede estar vacío");
        }

        // Si viene sin el namespace completo, prepéndelo
        $fullModelName = str_starts_with($modelName, $baseNamespace)
            ? $modelName
            : $baseNamespace . $modelName;

        // Verifica existencia de clase autoloadable
        if (!class_exists($fullModelName)) {
            throw new \RuntimeException("Modelo no encontrado: {$fullModelName}");
        }

        try {
            // Instancia el modelo pasándole la conexión (tu base Model la espera)
            return new $fullModelName($connectionName ?? 'default');
        } catch (\Throwable $e) {
            // Escribe log claro
            $logFile = base_dir("writer/logs/modelLoader.log");
            if (!is_dir(dirname($logFile))) { @mkdir(dirname($logFile), 0755, true); }
            @error_log("[".date('c')."] Error instanciando {$fullModelName}: ".$e->getMessage().PHP_EOL, 3, $logFile);

            // Re-lanza excepción “genérica” que ve el front
            throw new \Exception("No se pudo crear la instancia del modelo", 500, $e);
        }
    }
}

<?php

namespace App\Services\FileManagement;

use Exception;

/**
 * Gestor de transacciones seguras para archivos
 * 
 * Esta clase maneja operaciones de archivos de forma transaccional,
 * garantizando que los archivos originales no se pierdan en caso de error.
 * 
 * @package App\Services\FileManagement
 * @author Javier Falals
 * @version 1.0
 */
class SecureFileTransactionManager
{
    /**
     * @var string Ruta del expediente principal
     */
    private $rutaExpediente;
    
    /**
     * @var string Nombre del documento
     */
    private $documento;
    
    /**
     * @var string Directorio de adicionales
     */
    private $directorioAdicionales;
    
    /**
     * @var string Ruta del backup temporal
     */
    private $rutaBackup;
    
    /**
     * @var bool Indica si se creó un backup
     */
    private $backupCreado = false;
    
    /**
     * @var bool Indica si la transacción está iniciada
     */
    private $transaccionIniciada = false;
    
    /**
     * @var array Log de operaciones realizadas
     */
    private $logOperaciones = [];

    /**
     * Constructor del gestor de transacciones
     * 
     * @param string $rutaExpediente Ruta del expediente
     * @param string $documento Nombre del documento
     * @param string $directorioAdicionales Directorio de adicionales
     */
    public function __construct(string $rutaExpediente, string $documento, string $directorioAdicionales)
    {
        $this->rutaExpediente = rtrim($rutaExpediente, '/') . '/';
        $this->documento = $documento;
        $this->directorioAdicionales = $directorioAdicionales;
        $this->rutaBackup = $this->generarRutaBackup();
        
        $this->logOperacion("Inicializado gestor de transacciones", [
            'expediente' => $this->rutaExpediente,
            'documento' => $this->documento
        ]);
    }

    /**
     * Inicia una nueva transacción
     * 
     * @throws Exception Si ya hay una transacción en curso
     */
    public function iniciarTransaccion(): void
    {
        if ($this->transaccionIniciada) {
            throw new Exception("Ya existe una transacción en curso");
        }
        
        $this->transaccionIniciada = true;
        $this->logOperacion("Transacción iniciada");
    }

    /**
     * Crea backup de archivos existentes
     * 
     * @return array Resultado de la operación
     */
    public function crearBackupArchivosExistentes(): array
    {
        try {
            if (!$this->transaccionIniciada) {
                throw new Exception("No hay transacción iniciada");
            }

            if (!is_dir($this->rutaExpediente)) {
                $this->logOperacion("No hay archivos existentes para backup");
                return ['status' => true, 'message' => 'No hay archivos existentes para hacer backup'];
            }

            // Crear directorio de backup
            if (!$this->crearDirectorioBackup()) {
                throw new Exception('No se pudo crear el directorio de backup');
            }

            // Copiar archivos existentes al backup
            $this->copiarDirectorioRecursivo($this->rutaExpediente, $this->rutaBackup);
            $this->backupCreado = true;

            $this->logOperacion("Backup creado exitosamente", ['ruta_backup' => $this->rutaBackup]);
            return ['status' => true, 'message' => 'Backup creado exitosamente'];
            
        } catch (Exception $e) {
            $this->logOperacion("Error al crear backup", ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Error al crear backup: ' . $e->getMessage()];
        }
    }

    /**
     * Confirma la transacción y limpia recursos
     */
    public function confirmarTransaccion(): void
    {
        if (!$this->transaccionIniciada) {
            return;
        }

        try {
            if ($this->backupCreado && is_dir($this->rutaBackup)) {
                // Eliminar backup ya que todo salió bien
                helper("control_archivos_helper");
                eliminar_directorios($this->rutaBackup);
                $this->logOperacion("Backup eliminado tras confirmación exitosa");
            }
            
            $this->transaccionIniciada = false;
            $this->logOperacion("Transacción confirmada exitosamente");
            
        } catch (Exception $e) {
            $this->logOperacion("Error al confirmar transacción", ['error' => $e->getMessage()]);
            error_log("Error al confirmar transacción: " . $e->getMessage());
        }
    }

    /**
     * Revierte la transacción restaurando archivos originales
     */
    public function revertirTransaccion(): void
    {
        if (!$this->transaccionIniciada) {
            return;
        }

        try {
            $this->logOperacion("Iniciando rollback de transacción");
            
            if ($this->backupCreado && is_dir($this->rutaBackup)) {
                // Eliminar archivos actuales si existen
                if (is_dir($this->rutaExpediente)) {
                    helper("control_archivos_helper");
                    eliminar_directorios($this->rutaExpediente);
                    $this->logOperacion("Archivos actuales eliminados para rollback");
                }

                // Restaurar desde backup
                if (!mkdir($this->rutaExpediente, 0755, true)) {
                    throw new Exception("No se pudo recrear el directorio principal");
                }

                $this->copiarDirectorioRecursivo($this->rutaBackup, $this->rutaExpediente);
                $this->logOperacion("Archivos restaurados desde backup");
                
                // Limpiar backup
                eliminar_directorios($this->rutaBackup);
                $this->logOperacion("Backup limpiado tras rollback");
            }
            
            $this->logOperacion("Rollback completado exitosamente");
            
        } catch (Exception $e) {
            $this->logOperacion("Error durante rollback", ['error' => $e->getMessage()]);
            error_log("Error en rollback de archivos: " . $e->getMessage());
        } finally {
            $this->transaccionIniciada = false;
        }
    }

    /**
     * Obtiene el log de operaciones realizadas
     * 
     * @return array Log de operaciones
     */
    public function getLogOperaciones(): array
    {
        return $this->logOperaciones;
    }

    /**
     * Verifica si hay una transacción activa
     * 
     * @return bool
     */
    public function tieneTransaccionActiva(): bool
    {
        return $this->transaccionIniciada;
    }

    /**
     * Destructor - asegura limpieza en caso de no confirmación
     */
    public function __destruct()
    {
        if ($this->transaccionIniciada) {
            $this->logOperacion("Destructor ejecutado con transacción activa - ejecutando rollback");
            $this->revertirTransaccion();
        }
    }

    // ===== MÉTODOS PRIVADOS =====

    /**
     * Genera una ruta única para el backup
     * 
     * @return string
     */
    private function generarRutaBackup(): string
    {
        $timestamp = date('YmdHis');
        $unique = uniqid();
        $parentDir = dirname($this->rutaExpediente);
        
        return $parentDir . "/backup_{$timestamp}_{$unique}/";
    }

    /**
     * Crea el directorio de backup
     * 
     * @return bool
     */
    private function crearDirectorioBackup(): bool
    {
        if (!mkdir($this->rutaBackup, 0755, true)) {
            return false;
        }
        
        // Verificar que el directorio se creó correctamente
        return is_dir($this->rutaBackup) && is_writable($this->rutaBackup);
    }

    /**
     * Copia un directorio de forma recursiva
     * 
     * @param string $origen Directorio origen
     * @param string $destino Directorio destino
     * @throws Exception Si hay error en la copia
     */
    private function copiarDirectorioRecursivo(string $origen, string $destino): void
    {
        if (!is_dir($origen)) {
            throw new Exception("El directorio origen no existe: $origen");
        }

        $directorio = opendir($origen);
        if (!$directorio) {
            throw new Exception("No se pudo abrir el directorio: $origen");
        }
        
        while (($archivo = readdir($directorio)) !== false) {
            if ($archivo != '.' && $archivo != '..') {
                $rutaOrigen = $origen . $archivo;
                $rutaDestino = $destino . $archivo;
                
                if (is_dir($rutaOrigen)) {
                    if (!mkdir($rutaDestino, 0755, true)) {
                        closedir($directorio);
                        throw new Exception("No se pudo crear directorio: $rutaDestino");
                    }
                    $this->copiarDirectorioRecursivo($rutaOrigen . '/', $rutaDestino . '/');
                } else {
                    if (!copy($rutaOrigen, $rutaDestino)) {
                        closedir($directorio);
                        throw new Exception("No se pudo copiar archivo: $rutaOrigen -> $rutaDestino");
                    }
                }
            }
        }
        
        closedir($directorio);
    }

    /**
     * Registra una operación en el log interno
     * 
     * @param string $mensaje Mensaje a registrar
     * @param array $contexto Contexto adicional
     */
    private function logOperacion(string $mensaje, array $contexto = []): void
    {
        $this->logOperaciones[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'mensaje' => $mensaje,
            'contexto' => $contexto
        ];
    }
}
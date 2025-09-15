<?php

namespace App\Services\FileManagement;

use Exception;

/**
 * Clase para manejar la eliminación segura de documentos
 */
class SecureDocumentRemovalManager
{
    private int    $idIngreso;
    private string $documento;
    private string $documentoAdicionales = "ADIC";
    private string $rutaExpediente;
    private string $rutaArchivo;
    private bool   $esDocumentoAdicional;
    private string $backupPath;
    private bool   $transaccionIniciada = false;
    private string $rutaExpedientesEmpleados;

    // Mapeo de documentos a columnas de base de datos
    private const MAPEO_COLUMNAS = [
        "FOTO" => "---",
        "CVIT" => "---", 
        "ESCO" => "RUTA_TITULOS",
        "COCE" => "RUTA_CEDULA",
        "HODE" => "RUTA_HOJA_DELIN",
        "CAVA" => "RUTA_CARNE",
        "MADE" => "RUTA_MAN_DES",
        "HDPW" => "RUTA_HOJA_DELIN_PW",
    ];

    public function __construct(int $idIngreso, string $documento, string $RUTA_EXPEDIENTES_EMPLEADOS)
    {
        $this->idIngreso = $idIngreso;
        $this->documento = $documento;
        $this->esDocumentoAdicional = ($documento === $this->documentoAdicionales);
        $this->rutaExpedientesEmpleados = $RUTA_EXPEDIENTES_EMPLEADOS;
        $this->rutaExpediente = base_dir("{$RUTA_EXPEDIENTES_EMPLEADOS}/{$idIngreso}/{$documento}/");
        $this->backupPath = $this->generarRutaBackup();
    }

    /**
     * Valida que el documento pueda ser eliminado
     */
    public function validarDocumentoParaEliminacion(): array
    {
        try {
            // 1. Validar que el ingreso existe
            $datosIngreso = model("RH\Ingresos\IngresosModel")
                ->where("ID_INGRESO", $this->idIngreso)
                ->getFirstRow();

            if (empty($datosIngreso)) {
                return ['status' => false, 'message' => 'El ingreso especificado no existe'];
            }

            // 2. Para documentos adicionales, validar URL y archivo específico
            if ($this->esDocumentoAdicional) {
                return $this->validarDocumentoAdicional();
            }

            // 3. Para documentos regulares, validar directorio
            return $this->validarDocumentoRegular();

        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error al validar documento: ' . $e->getMessage()];
        }
    }

    /**
     * Ejecuta la eliminación segura del documento
     */
    public function ejecutarEliminacionSegura(): array
    {
        try {
            $this->transaccionIniciada = true;

            // 1. Crear backup del documento/directorio
            $backupResult = $this->crearBackupDocumento();
            if (!$backupResult['status']) {
                throw new Exception("Error al crear backup: " . $backupResult['message']);
            }

            // 2. Iniciar transacción de base de datos
            $DocumentacionesModel = model("RH\Ingresos\DocumentacionesModel");
            $SolicitudesModel = model("RH\Ingresos\SolicitudesModel");
            
            $DocumentacionesModel->init_transaction();

            try {
                // 3. Actualizar base de datos
                $actualizacionResult = $this->actualizarBaseDatos($DocumentacionesModel, $SolicitudesModel);
                if (!$actualizacionResult['status']) {
                    throw new Exception($actualizacionResult['message']);
                }

                // 4. Eliminar archivos/directorio
                $eliminacionResult = $this->eliminarArchivosDelSistema();
                if (!$eliminacionResult['status']) {
                    throw new Exception($eliminacionResult['message']);
                }

                // 5. Confirmar transacción
                $DocumentacionesModel->commit_transaction();
                
                // 6. Limpiar backup (eliminación exitosa)
                $this->limpiarBackup();

                return [
                    'status' => true, 
                    'message' => 'Documento removido correctamente'
                ];

            } catch (Exception $e) {
                // Rollback de base de datos
                $DocumentacionesModel->rollback_transaction();
                throw $e;
            }

        } catch (Exception $e) {
            // Restaurar archivos desde backup
            $this->restaurarDesdeBackup();
            
            return [
                'status' => false, 
                'message' => 'Error al eliminar documento: ' . $e->getMessage()
            ];
        } finally {
            $this->transaccionIniciada = false;
        }
    }

    /**
     * Valida documento adicional específico
     */
    private function validarDocumentoAdicional(): array
    {
        // Obtener la URL del documento desde POST (debería venir el archivo específico)
        $urlDocumento = $_POST["URL_DOCUMENTO"] ?? null;
        
        if (empty($urlDocumento)) {
            return ['status' => false, 'message' => 'URL del documento no especificada'];
        }

        // Validar que sea una URL local
        if (!is_local_url($urlDocumento)) {
            return ['status' => false, 'message' => 'URL de documento no válida'];
        }

        // Convertir URL a ruta de archivo
        $this->rutaArchivo = base_dir(str_replace(base_url(), "", $urlDocumento));

        // Validar que el archivo existe
        if (!is_file($this->rutaArchivo)) {
            return ['status' => false, 'message' => 'El archivo especificado no existe'];
        }

        // Validar que el archivo está dentro del directorio permitido
        $directorioBase = base_dir($this->rutaExpedientesEmpleados);
        if (!str_contains(dirname($this->rutaArchivo), $directorioBase)) {
            return ['status' => false, 'message' => 'El archivo no está en el directorio permitido'];
        }

        // Validar que es un documento adicional
        if (!str_contains($urlDocumento, $this->documentoAdicionales)) {
            return ['status' => false, 'message' => 'El archivo no es un documento adicional'];
        }

        return ['status' => true, 'message' => 'Documento adicional válido'];
    }

    /**
     * Valida documento regular
     */
    private function validarDocumentoRegular(): array
    {
        // Validar que el directorio existe
        if (!is_dir($this->rutaExpediente)) {
            return ['status' => false, 'message' => 'El documento especificado no existe'];
        }

        // Validar que el directorio no está vacío
        $archivos = array_diff(scandir($this->rutaExpediente), ['.', '..']);
        if (empty($archivos)) {
            return ['status' => false, 'message' => 'El directorio del documento está vacío'];
        }

        return ['status' => true, 'message' => 'Documento regular válido'];
    }

    /**
     * Crea backup del documento antes de eliminarlo
     */
    private function crearBackupDocumento(): array
    {
        try {
            // Crear directorio de backup
            if (!mkdir($this->backupPath, 0755, true)) {
                return ['status' => false, 'message' => 'No se pudo crear directorio de backup'];
            }

            if ($this->esDocumentoAdicional) {
                // Backup de archivo específico
                $nombreArchivo = basename($this->rutaArchivo);
                $rutaBackupArchivo = $this->backupPath . $nombreArchivo;
                
                if (!copy($this->rutaArchivo, $rutaBackupArchivo)) {
                    return ['status' => false, 'message' => 'Error al crear backup del archivo'];
                }
            } else {
                // Backup de directorio completo
                $this->copiarDirectorioRecursivo($this->rutaExpediente, $this->backupPath);
            }

            return ['status' => true, 'message' => 'Backup creado exitosamente'];

        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error al crear backup: ' . $e->getMessage()];
        }
    }

    /**
     * Actualiza la base de datos eliminando referencias al documento
     */
    private function actualizarBaseDatos($DocumentacionesModel, $SolicitudesModel): array
    {
        try {
            $datosIngreso = model("RH\Ingresos\IngresosModel")
                ->where("ID_INGRESO", $this->idIngreso)
                ->getFirstRow();

            $tipoDocumento = $this->esDocumentoAdicional ? $this->documentoAdicionales : $this->documento;
            
            $datosDocumentos = $DocumentacionesModel->where([
                "ID_INGRESO" => $this->idIngreso,
                "DOCUMENTACION" => $tipoDocumento,
            ])->getFirstRow();

            $registrosAfectados = 0;

            if (empty($datosDocumentos)) {
                // Actualizar tabla de solicitudes
                $columna = self::MAPEO_COLUMNAS[$this->documento] ?? null;
                if ($columna && $columna !== "---") {
                    $registrosAfectados = $SolicitudesModel->update(
                        [$columna => null],
                        $datosIngreso->getIDSOLICITUD()
                    );
                }
            } else {
            	$existen_documentos_adicionales = $this->esDocumentoAdicional && array_map(fn ($e) => !in_array($e, [".", ".."]), scandir(dirname($this->rutaArchivo))) > 1;
            	if ($existen_documentos_adicionales) {
	                // Mantener el registro, ya que aún existen adicionales
            		$registrosAfectados = 1;
            	} else {
	                // Eliminar de tabla de documentaciones
	                $registrosAfectados = $DocumentacionesModel->delete(
	                    $datosDocumentos->getIDDOCUMENTACION()
	                );
            	}
            }

            if ($registrosAfectados === 0) {
                return ['status' => false, 'message' => 'No se pudo actualizar la base de datos'];
            }

            return ['status' => true, 'message' => 'Base de datos actualizada correctamente'];
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error al actualizar base de datos: ' . $e->getMessage()];
        }
    }

    /**
     * Elimina los archivos del sistema de archivos
     */
    private function eliminarArchivosDelSistema(): array
    {
        try {
            if ($this->esDocumentoAdicional) {
                // Eliminar archivo específico
                if (!unlink($this->rutaArchivo)) {
                    return ['status' => false, 'message' => 'No se pudo eliminar el archivo'];
                }

                // Si era el último archivo del directorio, eliminar directorio
                $directorioArchivo = dirname($this->rutaArchivo);
                $archivosRestantes = array_diff(scandir($directorioArchivo), ['.', '..']);
                
                if (empty($archivosRestantes)) {
                    $resultado = eliminar_directorios($directorioArchivo);
                    if (!$resultado['status']) {
                        return ['status' => false, 'message' => 'Archivo eliminado pero no se pudo limpiar el directorio'];
                    }
                }
            } else {
                // Eliminar directorio completo
                $resultado = eliminar_directorios($this->rutaExpediente);
                if (!$resultado['status']) {
                    return ['status' => false, 'message' => $resultado['message']];
                }
            }

            return ['status' => true, 'message' => 'Archivos eliminados correctamente'];

        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error al eliminar archivos: ' . $e->getMessage()];
        }
    }

    /**
     * Restaura archivos desde backup en caso de error
     */
    private function restaurarDesdeBackup(): void
    {
        try {
            if (!is_dir($this->backupPath)) {
                return;
            }

            if ($this->esDocumentoAdicional) {
                // Restaurar archivo específico
                $archivosBackup = array_diff(scandir($this->backupPath), ['.', '..']);
                if (!empty($archivosBackup)) {
                    $archivoBackup = array_shift($archivosBackup);
                    $rutaBackupArchivo = $this->backupPath . $archivoBackup;
                    
                    // Asegurar que el directorio destino existe
                    $directorioDestino = dirname($this->rutaArchivo);
                    if (!is_dir($directorioDestino)) {
                        mkdir($directorioDestino, 0755, true);
                    }
                    
                    copy($rutaBackupArchivo, $this->rutaArchivo);
                }
            } else {
                // Restaurar directorio completo
                if (!is_dir($this->rutaExpediente)) {
                    mkdir($this->rutaExpediente, 0755, true);
                }
                $this->copiarDirectorioRecursivo($this->backupPath, $this->rutaExpediente);
            }

        } catch (Exception $e) {
            error_log("Error al restaurar backup: " . $e->getMessage());
        } finally {
            $this->limpiarBackup();
        }
    }

    /**
     * Limpia el directorio de backup
     */
    private function limpiarBackup(): void
    {
        try {
            if (is_dir($this->backupPath)) {
                eliminar_directorios($this->backupPath);
            }
        } catch (Exception $e) {
            error_log("Error al limpiar backup: " . $e->getMessage());
        }
    }

    /**
     * Genera ruta única para backup
     */
    private function generarRutaBackup(): string
    {
        $timestamp = date('YmdHis');
        $unique = uniqid();
        $baseDir = base_dir("temp/document_removal_backup/");
        
        return $baseDir . "backup_{$this->idIngreso}_{$this->documento}_{$timestamp}_{$unique}/";
    }

    /**
     * Copia directorio de forma recursiva
     */
    private function copiarDirectorioRecursivo(string $origen, string $destino): void
    {
        $directorio = opendir($origen);
        
        while (($archivo = readdir($directorio)) !== false) {
            if ($archivo != '.' && $archivo != '..') {
                $rutaOrigen = "{$origen}/{$archivo}";
                $rutaDestino = "{$destino}/{$archivo}";
                if (is_dir($rutaOrigen)) {
                    mkdir($rutaDestino, 0755, true);
                    $this->copiarDirectorioRecursivo($rutaOrigen . '/', $rutaDestino . '/');
                } else {
                    copy($rutaOrigen, $rutaDestino);
                }
            }
        }
        
        closedir($directorio);
    }

    /**
     * Destructor - limpia recursos en caso de no finalización correcta
     */
    public function __destruct()
    {
        if ($this->transaccionIniciada) {
            $this->restaurarDesdeBackup();
        }
    }
}
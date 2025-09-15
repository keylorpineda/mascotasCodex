<?php
namespace App\Services\DataBase;

use App\Config\DataBase\DatabaseConfig;
use App\Services\DataBase\DsnBuilder;

use PDO;
use PDOException;
use Exception;

/**
 * Factory para crear conexiones PDO
 */
class DatabaseConnectionFactory
{
    private DatabaseConfig $config;
    
    public function __construct(DatabaseConfig $config = null)
    {
        $this->config = $config ?? new DatabaseConfig();
    }
    
    /**
     * Crea una conexión PDO
     */
    public function createConnection(string $databaseName = 'default'): PDO
    {
        $config  = $this->config->getDatabaseConfig($databaseName);
        $dsn 	 = $this->buildDsn($config);
        $options = $this->getDefaultOptions($config);
        
        try {
            $connection = new PDO(
                $dsn, 
                $config['username'], 
                $config['password'],
                $options
            );
            
            // Configurar atributos post-conexión para SQL Server
            if ($config['driver'] === 'sqlsrv') {
                $this->configureSqlServerConnection($connection);
            }
            
            return $connection;
            
        } catch (PDOException $e) {
            throw new Exception(
                "Error al conectar con la base de datos '{$databaseName}': " . $e->getMessage()
            );
        }
    }
    
    /**
     * Construye el DSN basado en la configuración
     */
    private function buildDsn(array $config): string
    {
        $builder = new DsnBuilder();
        return $builder->build($config);
    }
    
    /**
     * Configuración específica para SQL Server después de la conexión
     */
    private function configureSqlServerConnection(PDO $connection): void
    {
        try {
            // Configuraciones que funcionan mejor después de establecer la conexión
            $connection->exec("SET ANSI_NULLS ON");
            $connection->exec("SET ANSI_WARNINGS ON");
            $connection->exec("SET QUOTED_IDENTIFIER ON");
        } catch (PDOException $e) {
            // Log del error pero no interrumpir la conexión
            error_log("Advertencia: No se pudieron establecer todas las configuraciones de SQL Server: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene las opciones por defecto para PDO según el driver
     */
    private function getDefaultOptions(array $config): array
    {
        $baseOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        
        // Opciones específicas por driver
        return match ($config['driver']) {
            'mysql' => (
            	$baseOptions + [
	                PDO::ATTR_TIMEOUT => 30,
	                PDO::ATTR_PERSISTENT => false,
	                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
	                PDO::ATTR_EMULATE_PREPARES => false,
	            ]
	        ),
            'sqlsrv' => (
            	$baseOptions + [
	                // SQL Server tiene opciones más limitadas
	                PDO::ATTR_CASE => PDO::CASE_NATURAL,
	                PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING,
	            ]
	        ),
            default => $baseOptions
        };
    }
}
<?php

namespace App\Config\Database;

use PDO;
use PDOException;
use Exception;
use InvalidArgumentException;

/**
 * Configuración y gestión de conexiones a bases de datos
 */
class DatabaseConfig
{
    private const DRIVER_MYSQL = 'mysql';
    private const DRIVER_SQLSRV = 'sqlsrv';
    
    private const REQUIRED_FIELDS = ['driver', 'host', 'username', 'password', 'dbname'];
    
    private array $configurations = [];
    private string $defaultDatabase = 'DEFAULT';
    
    public function __construct()
    {
        $this->initializeDefaults();
        $this->loadFromEnvironment();
    }
    
    /**
     * Inicializa las configuraciones por defecto
     */
    private function initializeDefaults(): void
    {
        $this->configurations = [
            'DEFAULT' => [
                'driver'   => self::DRIVER_MYSQL,
                'host'     => '',
                'username' => '',
                'password' => '',
                'dbname'   => '',
            ],
            // '' => [
            //     'driver'   => self::DRIVER_SQLSRV,
            //     'host'     => '',
            //     'username' => '',
            //     'password' => '',
            //     'dbname'   => '',
            // ]
        ];
    }
    
    /**
     * Carga configuraciones desde variables de entorno
     */
    private function loadFromEnvironment(): void
    {
        foreach ($_ENV as $key => $value) {
            if (!$this->isDatabaseEnvironmentVariable($key)) {
                continue;
            }
            
            $this->processEnvironmentVariable($key, $value);
        }
    }
    
    /**
     * Verifica si una variable de entorno es de configuración de base de datos
     */
    private function isDatabaseEnvironmentVariable(string $key): bool
    {
        return str_starts_with($key, 'database.');
    }
    
    /**
     * Procesa una variable de entorno específica
     */
    private function processEnvironmentVariable(string $key, string $value): void
    {
        $parts = explode('.', $key);
        
        if (count($parts) < 3) {
            return;
        }
        
        [, $databaseName, $configKey] = $parts;
        
        if (!$this->isValidDatabaseName($databaseName)) {
            return;
        }
        
        $this->setConfigurationValue($databaseName, $configKey, $value);
    }
    
    /**
     * Verifica si el nombre de la base de datos es válido
     */
    private function isValidDatabaseName(string $databaseName): bool
    {
        return isset($this->configurations[$databaseName]);
    }
    
    /**
     * Establece un valor de configuración
     */
    private function setConfigurationValue(string $databaseName, string $configKey, string $value): void
    {
        $mappedKey = $this->mapEnvironmentKey($configKey);
        
        if ($mappedKey && in_array($mappedKey, self::REQUIRED_FIELDS)) {
            $this->configurations[$databaseName][$mappedKey] = trim($value);
        }
    }
    
    /**
     * Mapea las claves de variables de entorno a claves de configuración
     */
    private function mapEnvironmentKey(string $envKey): ?string
    {
        $mapping = [
            'DBDriver' => 'driver',
            'hostname' => 'host',
            'username' => 'username',
            'password' => 'password',
            'database' => 'dbname',
        ];
        
        return $mapping[$envKey] ?? null;
    }
    
    /**
     * Obtiene la configuración de una base de datos
     */
    public function getDatabaseConfig(string $databaseName = 'default'): array
    {
        $actualName = $databaseName === 'default' ? $this->defaultDatabase : $databaseName;
        
        if (!isset($this->configurations[$actualName])) {
            throw new InvalidArgumentException(
                "La configuración de base de datos '{$actualName}' no existe"
            );
        }
        
        $config = $this->configurations[$actualName];
        
        $this->validateConfiguration($config, $actualName);
        
        return $config;
    }
    
    /**
     * Valida que la configuración tenga todos los campos requeridos
     */
    private function validateConfiguration(array $config, string $databaseName): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($config[$field])) {
                throw new Exception(
                    "Campo requerido '{$field}' faltante o vacío para la base de datos '{$databaseName}'"
                );
            }
        }
    }
    
    /**
     * Obtiene el driver de una base de datos específica
     */
    public function getDriver(string $databaseName = 'default'): string
    {
        $config = $this->getDatabaseConfig($databaseName);
        return $config['driver'];
    }
    
    /**
     * Obtiene todas las configuraciones disponibles
     */
    public function getAvailableDatabases(): array
    {
        return array_keys($this->configurations);
    }
}
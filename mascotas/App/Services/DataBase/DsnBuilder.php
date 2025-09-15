<?php
namespace App\Services\DataBase;

use InvalidArgumentException;

/**
 * Constructor de DSN para diferentes drivers
 */
class DsnBuilder
{
    private const DRIVER_MYSQL = 'mysql';
    private const DRIVER_SQLSRV = 'sqlsrv';
    
    public function build(array $config): string
    {
        return match ($config['driver']) {
            self::DRIVER_MYSQL => $this->buildMysqlDsn($config),
            self::DRIVER_SQLSRV => $this->buildSqlServerDsn($config),
            default => throw new InvalidArgumentException(
                "Driver no soportado: {$config['driver']}"
            )
        };
    }
    
    private function buildMysqlDsn(array $config): string
    {
        return sprintf(
            '%s:host=%s;dbname=%s;charset=utf8mb4',
            $config['driver'],
            $config['host'],
            $config['dbname']
        );
    }
    
    private function buildSqlServerDsn(array $config): string
    {
        return sprintf(
            '%s:server=%s;Database=%s;TrustServerCertificate=true',
            $config['driver'],
            $config['host'],
            $config['dbname']
        );
    }
}
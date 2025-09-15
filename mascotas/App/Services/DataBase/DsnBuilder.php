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
        $host = $config['host'] ?? '127.0.0.1';
        $port = 3306;

        // Permitir host con puerto embebido "localhost:3309"
        if (str_contains($host, ':')) {
            [$host, $maybePort] = explode(':', $host, 2);
            if (ctype_digit($maybePort)) {
                $port = (int) $maybePort;
            }
        }

        // Si se pasó explícitamente el port en config, tiene prioridad
        if (!empty($config['port'])) {
            $port = (int) $config['port'];
        }

        return sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['driver'],
            $host,
            $port,
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

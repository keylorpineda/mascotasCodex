<?php

// Funciones helper para mantener compatibilidad
if (!function_exists('get_database_connection')) {
    function get_database_connection(string $databaseName = 'default'): PDO
    {
        static $factory = null;
        
        if ($factory === null) {
            $factory = new \App\Services\DataBase\DatabaseConnectionFactory();
        }
        
        return $factory->createConnection($databaseName);
    }
}

if (!function_exists('get_database_driver')) {
    function get_database_driver(string $databaseName = 'default'): string
    {
        static $config = null;
        
        if ($config === null) {
            $config = new \App\Config\DataBase\DatabaseConfig();
        }
        
        return $config->getDriver($databaseName);
    }
}

if (!function_exists('get_available_databases')) {
    function get_available_databases(): array
    {
        static $config = null;
        
        if ($config === null) {
            $config = new \App\Config\DataBase\DatabaseConfig();
        }
        
        return $config->getAvailableDatabases();
    }
}

if (!function_exists("nombre_db")) {
    function get_driver(?string $nombre_db): string
    {
        return get_database_driver($nombre_db);
    }
}

if (!function_exists("data_base")) {
    function data_base(string $nombre_db = "default")
    {
        return get_database_connection($nombre_db);
    }
}

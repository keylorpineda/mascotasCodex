<?php

declare(strict_types=1);

namespace App\Core\Redirect\Interfaces;

/**
 * Interfaz para el manejo de sesiones
 */
interface SessionManagerInterface
{
    public function set(string $key, mixed $value): void;
    public function get(string $key, mixed $default = null): mixed;
    public function remove(string $key): void;
    public function has(string $key): bool;
}

<?php

declare(strict_types=1);

namespace App\Core\Redirect\Adapters;

use App\Core\Redirect\Interfaces\SessionManagerInterface;

/**
 * Adaptador para el sistema de sesiones del framework
 */
final class FrameworkSessionAdapter implements SessionManagerInterface
{
    private readonly object $session;

    public function __construct()
    {
        $this->session = session();
    }

    public function set(string $key, mixed $value): void
    {
        $this->session->set($key, $value, true);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->session->get($key) ?? $default;
    }

    public function remove(string $key): void
    {
        $this->session->remove($key);
    }

    public function has(string $key): bool
    {
        return $this->session->has($key);
    }
}

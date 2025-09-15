<?php

declare(strict_types=1);

namespace App\Core\Redirect\Interfaces;

/**
 * Interfaz para validación de URLs
 */
interface UrlValidatorInterface
{
    public function isValid(string $url): bool;
    public function isSafe(string $url): bool;
    public function normalize(string $url): string;
}

<?php

declare(strict_types=1);

namespace App\Core\Redirect\Validators;

use App\Core\Redirect\Interfaces\UrlValidatorInterface;

/**
 * Validador de URLs por defecto
 */
final class DefaultUrlValidator implements UrlValidatorInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly array $allowedHosts = []
    ) {}

    public function isValid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false || $this->isRelativeUrl($url);
    }

    public function isSafe(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        
        if (empty($host)) {
            return true; // URL relativa
        }

        $baseHost = parse_url($this->baseUrl, PHP_URL_HOST);
        return $host === $baseHost || in_array($host, $this->allowedHosts, true);
    }

    public function normalize(string $url): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $baseUrl = rtrim($this->baseUrl, '/');
        
        if (str_starts_with($url, '/')) {
            return $baseUrl . $url;
        }

        return $baseUrl . '/' . ltrim($url, '/');
    }

    private function isRelativeUrl(string $url): bool
    {
        return !str_contains($url, '://') && !str_starts_with($url, '//');
    }
}

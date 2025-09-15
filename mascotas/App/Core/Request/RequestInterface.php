<?php
declare(strict_types=1);

namespace App\Core\Request;

/**
 * Interface RequestInterface
 * Interfaz para el manejo de peticiones HTTP con funcionalidad extendida
 */
interface RequestInterface
{
    /**
     * Obtiene el método HTTP de la petición
     */
    public function getMethod(): string;

    /**
     * Obtiene la URI de la petición
     */
    public function getUri(): string;

    /**
     * Obtiene los segmentos de la URI
     * @param int|null $index Índice específico del segmento
     * @return array|string|null Array de segmentos, segmento específico o null
     */
    public function getSegments(?int $index = null): array|string|null;

    /**
     * Obtiene parámetros GET
     * @param string|null $key Clave específica del parámetro
     * @return mixed Valor del parámetro o array completo si no se especifica clave
     */
    public function getGet(?string $key = null): mixed;

    /**
     * Obtiene parámetros POST
     * @param string|null $key Clave específica del parámetro
     * @return mixed Valor del parámetro o array completo si no se especifica clave
     */
    public function getPost(?string $key = null): mixed;

    /**
     * Obtiene parámetros PUT
     * @param string|null $key Clave específica del parámetro
     * @return mixed Valor del parámetro o array completo si no se especifica clave
     */
    public function getPut(?string $key = null): mixed;

    /**
     * Obtiene parámetros DELETE
     * @param string|null $key Clave específica del parámetro
     * @return mixed Valor del parámetro o array completo si no se especifica clave
     */
    public function getDelete(?string $key = null): mixed;

    /**
     * Obtiene un header específico
     */
    public function getHeader(string $name): ?string;

    /**
     * Verifica si el método HTTP coincide con el especificado
     */
    public function isMethod(string $method): bool;

    /**
     * Obtiene todos los headers
     */
    public function getHeaders(): array;

    /**
     * Obtiene archivos subidos
     * @param string|null $key Clave específica del archivo
     * @return array|null Array de archivos o archivo específico
     */
    public function getFiles(?string $key = null): array|null;

    /**
     * Obtiene el cuerpo de la petición (para JSON, XML, etc.)
     */
    public function getBody(): string;

    /**
     * Obtiene datos JSON decodificados
     */
    public function getJson(): mixed;

    /**
     * Obtiene cookies
     * @param string|null $key Clave específica de la cookie
     * @return mixed Valor de la cookie o array completo si no se especifica clave
     */
    public function getCookie(?string $key = null): mixed;

    /**
     * Verifica si la petición es AJAX
     */
    public function isAjax(): bool;

    /**
     * Verifica si la petición es segura (HTTPS)
     */
    public function isSecure(): bool;

    /**
     * Obtiene la IP del cliente
     */
    public function getClientIp(): string;

    /**
     * Obtiene el User-Agent
     */
    public function getUserAgent(): ?string;
}
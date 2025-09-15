<?php
declare(strict_types=1);

namespace App\Core\Request\Exception;

use Exception;

/**
 * Se lanza cuando falla la decodificación de JSON en el cuerpo de la petición.
 */
class JsonDecodeException extends Exception
{
    public function __construct(string $message = 'Error al decodificar JSON', int $code = 0, Exception $previous = null)
    {
        parent::__construct("Error de decodificación JSON: {$message}", $code, $previous);
    }
}
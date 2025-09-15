<?php
declare(strict_types=1);

namespace App\Core\Request\Exception;

use Exception;

/**
 * Se lanza cuando el método HTTP no está en la lista de válidos.
 */
class InvalidHttpMethodException extends Exception
{
    public function __construct(string $method, int $code = 0, Exception $previous = null)
    {
        parent::__construct("Método HTTP inválido: {$method}", $code, $previous);
    }
}

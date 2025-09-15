<?php
declare(strict_types=1);

namespace App\Core\Request\Exception;

use Exception;

/**
 * Se lanza cuando la URI de la petición no cumple el formato esperado.
 */
class MalformedUriException extends Exception
{
    public function __construct(string $uri, int $code = 0, Exception $previous = null)
    {
        parent::__construct("URI malformada: {$uri}", $code, $previous);
    }
}
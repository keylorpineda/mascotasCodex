<?php
// App/Core/Routes/Exception/MiddlewareException.php

namespace App\Core\Routes\Exception;

use Exception;

class MiddlewareException extends Exception
{
    public function __construct(string $message = "Middleware error", int $code = 403, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
<?php
// App/Core/Routes/Exception/InvalidHandlerException.php

namespace App\Core\Routes\Exception;

use InvalidArgumentException;

class InvalidHandlerException extends InvalidArgumentException
{
    public function __construct(string $message = "Invalid handler format", int $code = 500, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
<?php
// App/Core/Routes/Exception/ControllerNotFoundException.php

namespace App\Core\Routes\Exception;

use Exception;

class ControllerNotFoundException extends Exception
{
    public function __construct(string $message = "Controller not found", int $code = 500, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
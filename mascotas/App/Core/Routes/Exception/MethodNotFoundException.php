<?php// App/Core/Routes/Exception/MethodNotFoundException.php

namespace App\Core\Routes\Exception;

use Exception;

class MethodNotFoundException extends Exception
{
    public function __construct(string $message = "Method not found", int $code = 500, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
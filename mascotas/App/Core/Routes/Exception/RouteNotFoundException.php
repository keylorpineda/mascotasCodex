<?php
// App/Core/Routes/Exception/RouteNotFoundException.php

namespace App\Core\Routes\Exception;

use Exception;

class RouteNotFoundException extends Exception
{
    public function __construct(string $message = "Route not found", int $code = 404, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
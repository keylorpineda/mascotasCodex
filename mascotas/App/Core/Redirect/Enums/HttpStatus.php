<?php

declare(strict_types=1);

namespace App\Core\Redirect\Enums;

/**
 * Enumeración para códigos de estado HTTP comunes
 */
enum HttpStatus: int
{
    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;
    case SEE_OTHER = 303;
    case TEMPORARY_REDIRECT = 307;
    case PERMANENT_REDIRECT = 308;
    case SERVICE_UNAVAILABLE = 503;
}

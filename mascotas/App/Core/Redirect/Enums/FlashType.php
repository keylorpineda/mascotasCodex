<?php

declare(strict_types=1);

namespace App\Core\Redirect\Enums;

/**
 * Enumeración para tipos de mensaje flash
 */
enum FlashType: string
{
    case SUCCESS = 'success';
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFO = 'info';
}
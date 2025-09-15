<?php

use App\Core\Redirect\RedirectManager;
use App\Core\Redirect\Enums\HttpStatus;
use App\Core\Redirect\Enums\FlashType;

if (!function_exists('redirect')) {
    function redirect(?string $url = null, ?HttpStatus $statusCode = null): RedirectManager
    {
        $manager = RedirectManager::getInstance();
        
        if ($url !== null) {
            $manager->to($url, $statusCode);
        }
        
        return $manager;
    }
}

if (!function_exists('redirect_back')) {
    function redirect_back(string $fallback = '/'): never
    {
        RedirectManager::getInstance()->back($fallback);
    }
}

if (!function_exists('redirect_with_message')) {
    function redirect_with_message(string $url, string $message, FlashType $type = FlashType::SUCCESS): never
    {
        RedirectManager::getInstance()->withMessage($message, $type)->to($url);
    }
}

if (!function_exists('redirect_with_errors')) {
    function redirect_with_errors(string $url, array $errors, ?array $input = null): never
    {
        RedirectManager::getInstance()->withErrors($errors, $input)->to($url);
    }
}

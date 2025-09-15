<?php

declare(strict_types=1);

namespace App\Core\Redirect;

use App\Core\Redirect\Enums\HttpStatus;
use App\Core\Redirect\Enums\FlashType;

use App\Core\Redirect\Interfaces\SessionManagerInterface;
use App\Core\Redirect\Interfaces\UrlValidatorInterface;
use App\Core\Redirect\Adapters\FrameworkSessionAdapter;
use App\Core\Redirect\Validators\DefaultUrlValidator;

use InvalidArgumentException;
use RuntimeException;

/**
 * Sistema completo de redirección
 */
final class RedirectManager
{
    private static ?self $instance = null;

    private function __construct(
        private readonly SessionManagerInterface $sessionManager,
        private readonly UrlValidatorInterface $urlValidator,
        private readonly HttpStatus $defaultStatusCode = HttpStatus::FOUND
    ) {}

    public static function create(
        ?SessionManagerInterface $sessionManager = null,
        ?UrlValidatorInterface $urlValidator = null,
        HttpStatus $defaultStatusCode = HttpStatus::FOUND
    ): self {
        $sessionManager ??= new FrameworkSessionAdapter();
        $urlValidator   ??= new DefaultUrlValidator(base_url());

        return new self($sessionManager, $urlValidator, $defaultStatusCode);
    }

    public static function getInstance(): self
    {
        return self::$instance ??= self::create();
    }

    /**
     * Redirección básica
     */
    public function to(string $url, ?HttpStatus $statusCode = null): never
    {
        $statusCode  ??= $this->defaultStatusCode;
        $normalizedUrl = $this->urlValidator->normalize($url);

        if (!$this->urlValidator->isValid($normalizedUrl)) {
            throw new InvalidArgumentException("URL inválida: {$url}");
        }

        if (!$this->urlValidator->isSafe($normalizedUrl)) {
            throw new InvalidArgumentException("URL no permitida por seguridad: {$url}");
        }

        $this->performRedirect($normalizedUrl, $statusCode);
    }

    /**
     * Redirección con datos de sesión personalizados
     */
    public function with(array $sessionData): self
    {
        foreach ($sessionData as $key => $value) {
            $this->sessionManager->set($key, $value);
        }

        return $this;
    }

    /**
     * Redirección con mensaje flash
     */
    public function withFlash(FlashData $flashData): self
    {
        return $this->with($flashData->toArray());
    }

    /**
     * Redirección con mensaje flash (método helper)
     */
    public function withMessage(string $message, FlashType $type = FlashType::SUCCESS): self
    {
        return $this->withFlash(new FlashData($message, $type));
    }

    /**
     * Redirección con errores de validación
     */
    public function withValidation(ValidationData $validationData): self
    {
        return $this->with($validationData->toArray());
    }

    /**
     * Redirección con errores (método helper)
     */
    public function withErrors(array $errors, ?array $input = null): self
    {
        $input ??= $this->getCurrentInput();
        return $this->withValidation(new ValidationData($errors, $input));
    }

    /**
     * Redirección con input anterior
     */
    public function withInput(?array $input = null): self
    {
        $input ??= $this->getCurrentInput();
        
        return $this->with([
            'old_input' => $input,
            'input_timestamp' => time()
        ]);
    }

    /**
     * Redirección a la página anterior
     */
    public function back(string $fallback = '/'): never
    {
        $previousUrl = $_SERVER['HTTP_REFERER'] ?? null;
        
        if ($previousUrl && $this->urlValidator->isSafe($previousUrl)) {
            $this->to($previousUrl);
        }

        $this->to($fallback);
    }

    /**
     * Redirección condicional
     */
    public function when(bool $condition, string $urlTrue, ?string $urlFalse = null): never
    {
        if ($condition) {
            $this->to($urlTrue);
        }

        if ($urlFalse !== null) {
            $this->to($urlFalse);
        }

        throw new RuntimeException('No se pudo determinar la URL de redirección');
    }

    /**
     * Redirección con autenticación requerida
     */
    public function toLogin(
        ?string $intendedUrl = null,
        string $loginUrl = '/login',
        ?string $message = null
    ): never {
        $sessionData = [];
        
        if ($intendedUrl !== null) {
            $sessionData['intended_url'] = $intendedUrl;
        }

        if ($message !== null) {
            $flashData = new FlashData($message, FlashType::WARNING);
            $sessionData = [...$sessionData, ...$flashData->toArray()];
        }

        $this->with($sessionData)->to($loginUrl);
    }

    /**
     * Redirección después del login exitoso
     */
    public function intended(string $default = '/dashboard'): never
    {
        $intendedUrl = $this->sessionManager->get('intended_url');
        $this->sessionManager->remove('intended_url');
        
        $this->to($intendedUrl ?? $default);
    }

    /**
     * Redirección con retraso (útil para mostrar mensajes)
     */
    public function delayed(
        string $url,
        int $seconds = 3,
        ?string $message = null,
        ?string $title = null
    ): never {
        if ($message !== null) {
            $this->withMessage($message, FlashType::INFO);
        }

        $normalizedUrl = $this->urlValidator->normalize($url);
        $html = $this->generateDelayedRedirectHtml($normalizedUrl, $seconds, $message, $title);
        
        $this->outputAndExit($html);
    }

    /**
     * Redirección externa con confirmación
     */
    public function external(string $url, bool $requireConfirmation = true): never
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("URL externa inválida: {$url}");
        }

        if ($requireConfirmation) {
            $html = $this->generateExternalRedirectConfirmation($url);
            $this->outputAndExit($html);
        }

        $this->performRedirect($url, HttpStatus::FOUND);
    }

    /**
     * Redirección AJAX-friendly
     */
    public function ajax(string $url, array $data = []): never
    {
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'redirect' => $this->urlValidator->normalize($url),
                'data' => $data
            ], JSON_THROW_ON_ERROR);
            exit;
        }

        $this->to($url);
    }

    // Métodos privados

    private function performRedirect(string $url, HttpStatus $statusCode): never
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        header("Location: {$url}", true, $statusCode->value);
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        exit;
    }

    private function getCurrentInput(): array
    {
        $input = [];
        
        if (!empty($_GET)) {
            $input = [...$input, ...$_GET];
        }
        
        if (!empty($_POST)) {
            $filteredPost = $_POST;
            unset($filteredPost['password'], $filteredPost['password_confirmation'], $filteredPost['_token']);
            $input = [...$input, ...$filteredPost];
        }
        
        return $input;
    }

    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function outputAndExit(string $html): never
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        echo $html;
        exit;
    }

    private function generateDelayedRedirectHtml(
        string $url,
        int $seconds,
        ?string $message,
        ?string $title
    ): string {
        $title = htmlspecialchars($title ?? 'Redirigiendo...', ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars(
            $message ?? "Redirigiendo en {$seconds} segundos...",
            ENT_QUOTES,
            'UTF-8'
        );
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <meta http-equiv="refresh" content="{$seconds};url={$safeUrl}">
                <title>{$title}</title>
                <style>
                    :root {
                        --primary-color: #3498db;
                        --background-color: #f8f9fa;
                        --card-background: #ffffff;
                        --text-color: #2c3e50;
                        --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    }
                    
                    * { box-sizing: border-box; }
                    
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        background: var(--background-color);
                        color: var(--text-color);
                        margin: 0;
                        padding: 2rem;
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    
                    .container {
                        background: var(--card-background);
                        padding: 3rem;
                        border-radius: 12px;
                        box-shadow: var(--shadow);
                        text-align: center;
                        max-width: 400px;
                        width: 100%;
                    }
                    
                    .spinner {
                        width: 60px;
                        height: 60px;
                        border: 4px solid #e3e3e3;
                        border-top: 4px solid var(--primary-color);
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                        margin: 0 auto 2rem;
                    }
                    
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                    
                    .message {
                        font-size: 1.1rem;
                        margin-bottom: 1.5rem;
                        line-height: 1.4;
                    }
                    
                    .countdown {
                        font-size: 2rem;
                        font-weight: bold;
                        color: var(--primary-color);
                        margin: 1.5rem 0;
                    }
                    
                    .link {
                        display: inline-block;
                        color: var(--primary-color);
                        text-decoration: none;
                        padding: 0.75rem 1.5rem;
                        border: 2px solid var(--primary-color);
                        border-radius: 6px;
                        transition: all 0.2s ease;
                    }
                    
                    .link:hover {
                        background: var(--primary-color);
                        color: white;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="spinner"></div>
                    <div class="message">{$message}</div>
                    <div class="countdown" id="countdown">{$seconds}</div>
                    <a href="{$safeUrl}" class="link">Continuar ahora</a>
                </div>
                <script>
                    let count = {$seconds};
                    const countdown = document.getElementById('countdown');
                    const timer = setInterval(() => {
                        count--;
                        countdown.textContent = count;
                        if (count <= 0) {
                            clearInterval(timer);
                            window.location.href = '{$safeUrl}';
                        }
                    }, 1000);
                </script>
            </body>
        </html>
        HTML;
    }

    private function generateExternalRedirectConfirmation(string $url): string
    {
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $displayUrl = strlen($url) > 60 ? substr($url, 0, 57) . '...' : $url;
        $safeDisplayUrl = htmlspecialchars($displayUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Redirección Externa</title>
                <style>
                    :root {
                        --danger-color: #e74c3c;
                        --secondary-color: #6c757d;
                        --background-color: #f8f9fa;
                        --card-background: #ffffff;
                        --text-color: #2c3e50;
                        --border-radius: 8px;
                        --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    }
                    
                    * { box-sizing: border-box; }
                    
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        background: var(--background-color);
                        color: var(--text-color);
                        margin: 0;
                        padding: 2rem;
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    
                    .container {
                        background: var(--card-background);
                        padding: 3rem;
                        border-radius: 12px;
                        box-shadow: var(--shadow);
                        text-align: center;
                        max-width: 500px;
                        width: 100%;
                    }
                    
                    .warning-icon {
                        font-size: 3rem;
                        color: var(--danger-color);
                        margin-bottom: 1rem;
                    }
                    
                    h2 {
                        color: var(--danger-color);
                        margin-bottom: 1rem;
                        font-size: 1.5rem;
                    }
                    
                    .warning-text {
                        color: var(--danger-color);
                        font-weight: 500;
                        margin: 1.5rem 0;
                    }
                    
                    .url-display {
                        background: #f8f9fa;
                        padding: 1rem;
                        border-radius: var(--border-radius);
                        word-break: break-all;
                        margin: 1.5rem 0;
                        border: 1px solid #dee2e6;
                        font-family: Monaco, Consolas, monospace;
                        font-size: 0.9rem;
                    }
                    
                    .buttons {
                        margin-top: 2rem;
                        display: flex;
                        gap: 1rem;
                        justify-content: center;
                        flex-wrap: wrap;
                    }
                    
                    .btn {
                        padding: 0.75rem 2rem;
                        border: none;
                        border-radius: var(--border-radius);
                        cursor: pointer;
                        text-decoration: none;
                        font-weight: 500;
                        transition: all 0.2s ease;
                        display: inline-block;
                    }
                    
                    .btn-danger {
                        background: var(--danger-color);
                        color: white;
                    }
                    
                    .btn-danger:hover {
                        background: #c0392b;
                    }
                    
                    .btn-secondary {
                        background: var(--secondary-color);
                        color: white;
                    }
                    
                    .btn-secondary:hover {
                        background: #545b62;
                    }
                    
                    @media (max-width: 480px) {
                        .buttons {
                            flex-direction: column;
                        }
                        
                        .btn {
                            width: 100%;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="warning-icon">⚠️</div>
                    <h2>Redirección Externa</h2>
                    <p class="warning-text">Estás a punto de salir de nuestro sitio web</p>
                    <div class="url-display">{$safeDisplayUrl}</div>
                    <p>¿Deseas continuar a este sitio externo?</p>
                    <div class="buttons">
                        <a href="{$safeUrl}" class="btn btn-danger">Continuar</a>
                        <a href="javascript:history.back()" class="btn btn-secondary">Cancelar</a>
                    </div>
                </div>
            </body>
        </html>
        HTML;
    }
}
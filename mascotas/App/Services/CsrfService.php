<?php
namespace App\Services;
use DateTime;
class CsrfService {
    private string $token;
    private ?int $lifetime; // en segundos, null significa sin expiración
    private string $name_token = 'csrf_token';
    private string $name_time_token = 'csrf_token_time';

    /**
     * Constructor del servicio CSRF.
     * @param int|null $lifetime Tiempo de vida del token en segundos (por defecto, 10 minutos). 
     *                         Usar null para tokens sin expiración.
     * @throws RuntimeException Si la sesión no está iniciada
     */
    public function __construct(?int $lifetime = 600)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \RuntimeException("La sesión no está iniciada. Ejecuta session_start() antes de usar CsrfService.");
        }
        $this->lifetime = $lifetime;
        if (!isset($_SESSION[$this->name_token]) || !$this->token_sigue_vigente()) {
            $this->regenerar_token();
        } else {
            $this->token = $_SESSION[$this->name_token];
        }
    }

    /**
     * Establece el tiempo de vida del token.
     * @param int|null $lifetime Tiempo en segundos, o null para eliminar la expiración
     * @return self
     */
    public function setLifetime(?int $lifetime): self
    {
        $this->lifetime = $lifetime;
        return $this;
    }

    /**
     * Deshabilita la expiración del token.
     * @return self
     */
    public function sinExpiracion(): self
    {
        $this->lifetime = null;
        return $this;
    }

    /**
     * Genera y retorna el token CSRF.
     * @return string
     */
    public function obtener_token(): string
    {
        return $this->token;
    }

    /**
     * Regenera el token CSRF y lo guarda en la sesión.
     */
    public function regenerar_token(): void
    {
        $this->token = bin2hex(random_bytes(32));
        $_SESSION[$this->name_token] = $this->token;
        
        // Solo guardamos el tiempo si hay un lifetime definido
        if ($this->lifetime !== null) {
            $_SESSION[$this->name_time_token] = time();
        } else {
            // Si no hay expiración, eliminamos cualquier tiempo guardado previamente
            if (isset($_SESSION[$this->name_time_token])) {
                unset($_SESSION[$this->name_time_token]);
            }
        }
    }

    /**
     * Verifica si el token dado es válido y vigente.
     * @param string $token
     * @return bool
     */
    public function verificar_token(string $token): bool
    {
        return !$this->token_sigue_vigente()
            ? false
            : hash_equals($this->token, $token);
    }

    /**
     * Verifica si el token ha expirado.
     * @return bool
     */
    private function token_sigue_vigente(): bool
    {
        // Si lifetime es null, el token no expira
        if ($this->lifetime === null) {
            return isset($_SESSION[$this->name_token]);
        }
        
        // Si no hay tiempo guardado, el token no es válido
        if (!isset($_SESSION[$this->name_time_token])) {
            return false;
        }
        
        // Comprobamos si el token ha expirado
        return (time() - $_SESSION[$this->name_time_token]) <= $this->lifetime;
    }
}
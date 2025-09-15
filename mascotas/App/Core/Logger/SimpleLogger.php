<?php
// App/Core/Logger/SimpleLogger.php

namespace App\Core\Logger;

use DateTime;

class SimpleLogger
{
    private const LOG_LEVELS = [
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7,
    ];

    public function __construct(
        private ?string $logPath = null,
        private string $maxLogLevel = 'debug',
        private bool $enabled = true,
        private int $maxFileSize = 10485760, // 10MB
        private int $maxFiles = 5
    ) {
        $this->logPath = $logPath ?? $this->getDefaultLogPath();
        $this->maxLogLevel = self::LOG_LEVELS[strtolower($maxLogLevel)] ?? 7;
        $this->enabled = $enabled && $this->isWritable();
        $this->maxFileSize = $maxFileSize;
        $this->maxFiles = $maxFiles;
        
        $this->ensureLogDirectory();
    }

    /**
     * Log a message at emergency level
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Log a message at alert level
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Log a message at critical level
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Log a message at error level
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log a message at warning level
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log a message at notice level
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Log a message at info level
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log a message at debug level
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Log a message with any level
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $level = strtolower($level);
        
        if (!isset(self::LOG_LEVELS[$level])) {
            $level = 'info';
        }

        if (self::LOG_LEVELS[$level] > $this->maxLogLevel) {
            return;
        }

        $logEntry = $this->formatMessage($level, $message, $context);
        $this->writeToFile($logEntry);
    }

    /**
     * Format the log message
     */
    private function formatMessage(string $level, string $message, array $context): string
    {
        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        
        // Interpolar contexto en el mensaje
        $message = $this->interpolate($message, $context);
        
        // Obtener información adicional del request si está disponible
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $contextStr = '';
        if (!empty($context)) {
            $contextStr = ' | Context: ' . json_encode($context);
        }
        
        return "[{$timestamp}] [{$levelUpper}] [{$ip}] [{$method} {$uri}] {$message}{$contextStr}" . PHP_EOL;
    }

    /**
     * Interpolate context values into message placeholders
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        
        return strtr($message, $replace);
    }

    /**
     * Write log entry to file
     */
    private function writeToFile(string $logEntry): void
    {
        $currentLogFile = $this->getCurrentLogFile();
        
        // Rotar archivo si es necesario
        if (file_exists($currentLogFile) && filesize($currentLogFile) > $this->maxFileSize) {
            $this->rotateLogFiles();
        }
        
        // Escribir al archivo
        if (file_put_contents($currentLogFile, $logEntry, FILE_APPEND | LOCK_EX) === false) {
            // Fallback a error_log si no se puede escribir al archivo
            error_log("SimpleLogger: Failed to write to log file. Original message: " . trim($logEntry) . PHP_EOL, 3, base_dir("writer/logs/simple_logger.log"));
        }
    }

    /**
     * Get current log file path
     */
    private function getCurrentLogFile(): string
    {
        $date = date('Y-m-d');
        return $this->logPath . "/router-{$date}.log";
    }

    /**
     * Rotate log files
     */
    private function rotateLogFiles(): void
    {
        $currentFile = $this->getCurrentLogFile();
        $timestamp = date('Y-m-d_H-i-s');
        $rotatedFile = $this->logPath . "/router-{$timestamp}.log";
        
        if (file_exists($currentFile)) {
            rename($currentFile, $rotatedFile);
        }
        
        // Limpiar archivos antiguos
        $this->cleanOldLogFiles();
    }

    /**
     * Clean old log files
     */
    private function cleanOldLogFiles(): void
    {
        $files = glob($this->logPath . '/router-*.log');
        
        if (count($files) <= $this->maxFiles) {
            return;
        }
        
        // Ordenar por fecha de modificación (más antiguos primero)
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Eliminar archivos más antiguos que excedan el límite
        $filesToDelete = array_slice($files, 0, count($files) - $this->maxFiles);
        foreach ($filesToDelete as $file) {
            unlink($file);
        }
    }

    /**
     * Get default log path
     */
    private function getDefaultLogPath(): string
    {
        // Intentar usar diferentes ubicaciones según la estructura del proyecto
        $possiblePaths = [
            dirname(__DIR__, 3) . '/writer/logs',    // Project/writer/logs
            dirname(__DIR__, 3) . '/logs',           // App/logs
            dirname(__DIR__, 4) . '/logs',           // Project/logs
            dirname(__DIR__, 3) . '/storage/logs',   // App/storage/logs
            sys_get_temp_dir() . '/app-logs'         // Sistema temporal
        ];

        foreach ($possiblePaths as $path) {
            if (is_dir(dirname($path)) && is_writable(dirname($path))) {
                return $path;
            }
        }
        
        return sys_get_temp_dir() . '/app-logs';
    }

    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectory(): void
    {
        if (!is_dir($this->logPath)) {
            if (!mkdir($this->logPath, 0755, true) && !is_dir($this->logPath)) {
                $this->enabled = false;
                error_log("SimpleLogger: Failed to create log directory: {$this->logPath}" . PHP_EOL, 3, base_dir("writer/logs/simple_logger.log"));
            }
        }
    }

    /**
     * Check if log path is writable
     */
    private function isWritable(): bool
    {
        // Si el directorio no existe, verificar si podemos crearlo
        if (!is_dir($this->logPath)) {
            $parentDir = dirname($this->logPath);
            return is_dir($parentDir) && is_writable($parentDir);
        }
        
        return is_writable($this->logPath);
    }

    /**
     * Set log level
     */
    public function setLogLevel(string $level): void
    {
        $level = strtolower($level);
        if (isset(self::LOG_LEVELS[$level])) {
            $this->maxLogLevel = self::LOG_LEVELS[$level];
        }
    }

    /**
     * Enable/disable logging
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled && $this->isWritable();
    }

    /**
     * Get current log level
     */
    public function getLogLevel(): string
    {
        return array_search($this->maxLogLevel, self::LOG_LEVELS) ?: 'debug';
    }

    /**
     * Check if logging is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get log file path
     */
    public function getLogPath(): string
    {
        return $this->logPath;
    }

    /**
     * Get recent log entries
     */
    public function getRecentLogs(int $lines = 100): array
    {
        $currentLogFile = $this->getCurrentLogFile();
        
        if (!file_exists($currentLogFile)) {
            return [];
        }
        
        $content = file_get_contents($currentLogFile);
        if ($content === false) {
            return [];
        }
        
        $logLines = explode(PHP_EOL, trim($content));
        return array_slice($logLines, -$lines);
    }

    /**
     * Clear current log file
     */
    public function clearLogs(): bool
    {
        $currentLogFile = $this->getCurrentLogFile();
        
        if (file_exists($currentLogFile)) {
            return unlink($currentLogFile);
        }
        
        return true;
    }
}
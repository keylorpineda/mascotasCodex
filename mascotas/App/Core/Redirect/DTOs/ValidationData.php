<?php

declare(strict_types=1);

namespace App\Core\Redirect\DTOs;

/**
 * DTO para errores de validación
 */
readonly class ValidationData
{
    public function __construct(
        public array $errors,
        public ?array $input = null,
        public int $timestamp = 0
    ) {}

    public function toArray(): array
    {
        $data = [
            'validation_errors' => $this->errors,
            'validation_timestamp' => $this->timestamp ?: time()
        ];

        if ($this->input !== null) {
            $data['old_input'] = $this->input;
            $data['input_timestamp'] = $this->timestamp ?: time();
        }

        return $data;
    }
}

<?php

declare(strict_types=1);

namespace App\Core\Redirect\DTOs;

/**
 * DTO para datos de sesión flash
 */
readonly class FlashData
{
    public function __construct(
        public string $message,
        public FlashType $type = FlashType::SUCCESS,
        public int $timestamp = 0
    ) {}

    public function toArray(): array
    {
        return [
            'flash_message' => $this->message,
            'flash_type' => $this->type->value,
            'flash_timestamp' => $this->timestamp ?: time()
        ];
    }
}

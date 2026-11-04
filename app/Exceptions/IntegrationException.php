<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class IntegrationException extends RuntimeException
{
    public function __construct(
        public readonly string $provider,
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?string $responseBody = null,
    ) {
        parent::__construct("[{$provider}] {$message}");
    }
}

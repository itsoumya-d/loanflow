<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class InvalidWebhookSignatureException extends RuntimeException
{
    public function __construct(string $provider)
    {
        parent::__construct("Invalid webhook signature for [{$provider}].");
    }
}

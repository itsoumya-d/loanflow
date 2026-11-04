<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class IdempotencyConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The Idempotency-Key was reused with a different payload.');
    }
}

<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class InvalidStatusTransitionException extends RuntimeException
{
    public static function between(string $from, string $to): self
    {
        return new self("Cannot transition a loan application from [{$from}] to [{$to}].");
    }
}

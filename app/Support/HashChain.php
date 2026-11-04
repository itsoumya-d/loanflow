<?php

declare(strict_types=1);

namespace App\Support;

use JsonException;

final class HashChain
{
    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws JsonException
     */
    public static function next(?string $previousHash, array $payload): string
    {
        $canonical = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', ($previousHash ?? 'GENESIS').'|'.$canonical);
    }
}

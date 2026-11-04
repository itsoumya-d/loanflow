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
        $canonical = json_encode(
            self::canonicalize($payload),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        return hash('sha256', ($previousHash ?? 'GENESIS').'|'.$canonical);
    }

    /**
     * Recursively sort associative arrays so MySQL JSON normalisation cannot
     * change the hash input between write and verification.
     */
    public static function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(self::canonicalize(...), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = self::canonicalize($item);
        }

        return $value;
    }
}

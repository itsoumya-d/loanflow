<?php

declare(strict_types=1);

namespace App\Integrations\Keap;

class KeapWebhookVerifier
{
    public function __construct(private readonly array $config) {}

    public function verify(string $rawBody, ?string $signature): bool
    {
        $secret = (string) ($this->config['webhook_secret'] ?? '');

        if ($secret === '' || $signature === null || $signature === '') {
            return false;
        }

        $signature = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;
        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }
}

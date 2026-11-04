<?php

declare(strict_types=1);

namespace App\Integrations\Keap;

use App\Exceptions\IntegrationException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;

class KeapTokenProvider
{
    public function __construct(
        private readonly Factory $http,
        private readonly array $config,
    ) {}

    public function token(): string
    {
        $cacheKey = 'loanflow:keap:access_token';

        if (is_string($cached = Cache::get($cacheKey)) && $cached !== '') {
            return $cached;
        }

        $payload = [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ];

        if (! empty($this->config['refresh_token'])) {
            $payload['grant_type'] = 'refresh_token';
            $payload['refresh_token'] = $this->config['refresh_token'];
        } else {
            $payload['grant_type'] = 'client_credentials';
        }

        $response = $this->http
            ->asForm()
            ->timeout((int) ($this->config['timeout_seconds'] ?? 10))
            ->post((string) $this->config['token_url'], $payload);

        if (! $response->successful()) {
            throw new IntegrationException('keap', 'Token request failed.', $response->status(), $response->body());
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new IntegrationException('keap', 'Token response did not contain an access_token.');
        }

        $ttl = max(60, (int) ($response->json('expires_in') ?? 3600) - 60);
        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }
}

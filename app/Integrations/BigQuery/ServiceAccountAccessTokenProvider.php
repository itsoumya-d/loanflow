<?php

declare(strict_types=1);

namespace App\Integrations\BigQuery;

use App\Exceptions\IntegrationException;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;

class ServiceAccountAccessTokenProvider implements AccessTokenProvider
{
    public function __construct(
        private readonly Factory $http,
        private readonly array $config,
    ) {}

    public function token(): string
    {
        $credentials = $this->credentials();
        $cacheKey = 'loanflow:bigquery:token:'.sha1((string) ($credentials['client_email'] ?? 'default'));

        if (is_string($cached = Cache::get($cacheKey)) && $cached !== '') {
            return $cached;
        }

        $now = time();
        $claims = [
            'iss' => $credentials['client_email'],
            'scope' => (string) ($this->config['scope'] ?? 'https://www.googleapis.com/auth/bigquery.insertdata'),
            'aud' => (string) ($this->config['token_url'] ?? 'https://oauth2.googleapis.com/token'),
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $jwt = JWT::encode(
            $claims,
            (string) $credentials['private_key'],
            'RS256',
            $credentials['private_key_id'] ?? null,
        );

        $response = $this->http
            ->asForm()
            ->timeout((int) ($this->config['timeout_seconds'] ?? 15))
            ->post((string) ($this->config['token_url'] ?? 'https://oauth2.googleapis.com/token'), [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if (! $response->successful()) {
            throw new IntegrationException('bigquery', 'Google token request failed.', $response->status(), $response->body());
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new IntegrationException('bigquery', 'Google token response did not contain an access_token.');
        }

        $ttl = max(60, (int) ($response->json('expires_in') ?? 3600) - 60);
        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function credentials(): array
    {
        $json = $this->config['service_account_json'] ?? null;

        if (is_string($json) && $json !== '') {
            if (! str_starts_with(ltrim($json), '{')) {
                $decoded = base64_decode($json, true);

                if ($decoded === false) {
                    throw new IntegrationException('bigquery', 'BIGQUERY_SERVICE_ACCOUNT_JSON is not valid JSON or base64 JSON.');
                }

                $json = $decoded;
            }

            $data = json_decode($json, true);
        } else {
            $path = $this->config['service_account_path'] ?? null;

            if (! is_string($path) || $path === '' || ! is_readable($path)) {
                throw new IntegrationException('bigquery', 'No readable service account credentials configured.');
            }

            $data = json_decode((string) file_get_contents($path), true);
        }

        if (! is_array($data) || ! isset($data['client_email'], $data['private_key'])) {
            throw new IntegrationException('bigquery', 'Service account credentials are incomplete.');
        }

        return $data;
    }
}

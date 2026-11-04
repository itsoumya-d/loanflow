<?php

declare(strict_types=1);

namespace App\Integrations\Keap;

use App\Exceptions\IntegrationException;
use App\Models\LoanApplication;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;

class KeapClient
{
    public function __construct(
        private readonly Factory $http,
        private readonly KeapTokenProvider $tokens,
        private readonly array $config,
    ) {}

    public function upsertContact(LoanApplication $application): string
    {
        $existing = $this->findContactByEmail($application->email);

        if ($existing !== null && isset($existing['id'])) {
            $this->updateContact((string) $existing['id'], $application);

            return (string) $existing['id'];
        }

        $response = $this->send('post', '/crm/rest/v2/contacts', $this->payload($application));
        $id = $response->json('id');

        if (! is_scalar($id) || (string) $id === '') {
            throw new IntegrationException('keap', 'Contact creation response did not contain an id.', $response->status(), $response->body());
        }

        return (string) $id;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findContactByEmail(string $email): ?array
    {
        $filter = rawurlencode("email=={$email}");
        $response = $this->send('get', "/crm/rest/v2/contacts?filter={$filter}");

        $contacts = $response->json('contacts');

        if (! is_array($contacts) || $contacts === []) {
            return null;
        }

        $first = $contacts[0];

        return is_array($first) ? $first : null;
    }

    public function updateContact(string $contactId, LoanApplication $application): void
    {
        $this->send('patch', '/crm/rest/v2/contacts/'.rawurlencode($contactId), $this->payload($application));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(string $method, string $path, array $payload = []): Response
    {
        $url = rtrim((string) $this->config['base_url'], '/').$path;
        $attempts = max(1, (int) ($this->config['retry_attempts'] ?? 3));
        $baseMs = max(1, (int) ($this->config['retry_base_ms'] ?? 200));
        $lastResponse = null;
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $request = $this->http
                    ->withToken($this->tokens->token())
                    ->acceptJson()
                    ->asJson()
                    ->timeout((int) ($this->config['timeout_seconds'] ?? 10))
                    ->withHeaders(['X-Idempotency-Key' => $this->idempotencyKey($method, $path, $payload)]);

                $response = match ($method) {
                    'get' => $request->get($url),
                    'post' => $request->post($url, $payload),
                    'patch' => $request->patch($url, $payload),
                    default => throw new \InvalidArgumentException("Unsupported HTTP method [{$method}]."),
                };

                if ($response->successful()) {
                    return $response;
                }

                $lastResponse = $response;

                if (! in_array($response->status(), [429, 500, 502, 503, 504], true)) {
                    break;
                }

                $retryAfter = (int) $response->header('Retry-After');
                $sleepMs = $retryAfter > 0 ? $retryAfter * 1000 : $baseMs * (2 ** ($attempt - 1));
                usleep($sleepMs * 1000);
            } catch (\Throwable $exception) {
                $lastException = $exception;
                usleep($baseMs * (2 ** ($attempt - 1)) * 1000);
            }
        }

        if ($lastResponse !== null) {
            throw new IntegrationException('keap', 'API request failed.', $lastResponse->status(), $lastResponse->body());
        }

        throw new IntegrationException('keap', 'API request failed: '.($lastException?->getMessage() ?? 'unknown error'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function idempotencyKey(string $method, string $path, array $payload): string
    {
        return hash('sha256', $method.'|'.$path.'|'.json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(LoanApplication $application): array
    {
        $parts = preg_split('/\s+/', trim($application->applicant_name)) ?: [];
        $givenName = $parts[0] ?? $application->applicant_name;
        $familyName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

        $customFields = [];
        $mapping = $this->config['custom_fields'] ?? [];

        foreach ([
            'loan_reference' => $application->reference,
            'loan_amount' => (string) ($application->loan_amount_cents / 100),
            'loan_status' => $application->status,
        ] as $key => $value) {
            if (! empty($mapping[$key])) {
                $customFields[] = ['id' => $mapping[$key], 'content' => $value];
            }
        }

        return array_filter([
            'given_name' => $givenName,
            'family_name' => $familyName,
            'email_addresses' => [['field' => 'EMAIL1', 'email' => $application->email]],
            'phone_numbers' => [['field' => 'PHONE1', 'number' => $application->phone]],
            'custom_fields' => $customFields,
            'source_type' => 'WEBFORM',
        ], static fn (mixed $value): bool => ! empty($value));
    }
}

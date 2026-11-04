<?php

declare(strict_types=1);

namespace App\Integrations\Gtm;

use App\Exceptions\IntegrationException;
use App\Models\LoanEvent;
use Illuminate\Http\Client\Factory;

class GtmEventForwarder
{
    public function __construct(
        private readonly Factory $http,
        private readonly array $config,
    ) {}

    public function forward(LoanEvent $event): void
    {
        $url = (string) ($this->config['server_url'] ?? '');

        if ($url === '') {
            throw new IntegrationException('gtm', 'GTM server URL is not configured.');
        }

        $event->loadMissing('application');

        $payload = [
            'event' => 'loanflow_'.str_replace('.', '_', $event->type),
            'event_id' => $event->getKey(),
            'application_reference' => $event->application->reference,
            'occurred_at' => $event->occurred_at->toIso8601String(),
            'parameters' => [
                'loan_status' => $event->application->status,
                'loan_purpose' => $event->application->loan_purpose,
                'loan_amount' => (float) ($event->application->loan_amount_cents / 100),
                'source' => $event->application->source,
            ],
        ];

        $raw = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $signature = $this->sign($raw);

        $response = $this->http
            ->withHeaders([(string) ($this->config['signature_header'] ?? 'X-Loanflow-Signature') => $signature])
            ->acceptJson()
            ->withBody($raw, 'application/json')
            ->timeout((int) ($this->config['timeout_seconds'] ?? 10))
            ->post($url);

        if (! $response->successful()) {
            throw new IntegrationException('gtm', 'GTM forwarding failed.', $response->status(), $response->body());
        }

        $event->markGtmForwarded();
    }

    public function sign(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, (string) ($this->config['forward_secret'] ?? ''));
    }
}

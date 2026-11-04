<?php

declare(strict_types=1);

namespace App\Integrations\BigQuery;

use App\Exceptions\IntegrationException;
use App\Models\LoanEvent;
use Illuminate\Http\Client\Factory;

class BigQueryExporter
{
    public function __construct(
        private readonly Factory $http,
        private readonly AccessTokenProvider $tokens,
        private readonly array $config,
    ) {}

    public function export(LoanEvent $event): void
    {
        $project = (string) ($this->config['project_id'] ?? '');
        $dataset = (string) ($this->config['dataset'] ?? '');
        $table = (string) ($this->config['table'] ?? '');

        if ($project === '' || $dataset === '' || $table === '') {
            throw new IntegrationException('bigquery', 'BigQuery project, dataset or table is not configured.');
        }

        $event->loadMissing('application');
        $application = $event->application;

        $row = [
            'insertId' => $event->getKey(),
            'json' => [
                'event_id' => $event->getKey(),
                'application_reference' => $application->reference,
                'event_type' => $event->type,
                'occurred_at' => $event->occurred_at->toIso8601String(),
                'payload' => json_encode($event->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'environment' => app()->environment(),
                'schema_version' => 1,
            ],
        ];

        $url = sprintf(
            '%s/projects/%s/datasets/%s/tables/%s/insertAll',
            rtrim((string) ($this->config['api_url'] ?? 'https://bigquery.googleapis.com/bigquery/v2'), '/'),
            rawurlencode($project),
            rawurlencode($dataset),
            rawurlencode($table),
        );

        $attempts = 3;
        $lastResponse = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $response = $this->http
                ->withToken($this->tokens->token())
                ->acceptJson()
                ->asJson()
                ->timeout((int) ($this->config['timeout_seconds'] ?? 15))
                ->post($url, ['rows' => [$row]]);

            if ($response->successful()) {
                $event->markBigQueryExported();

                return;
            }

            $lastResponse = $response;

            if (! in_array($response->status(), [429, 500, 502, 503, 504], true)) {
                break;
            }

            usleep(200_000 * $attempt);
        }

        throw new IntegrationException(
            'bigquery',
            'Event export failed.',
            $lastResponse->status(),
            $lastResponse->body(),
        );
    }
}

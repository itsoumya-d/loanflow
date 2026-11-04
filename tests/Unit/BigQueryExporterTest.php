<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\IntegrationException;
use App\Integrations\BigQuery\AccessTokenProvider;
use App\Integrations\BigQuery\BigQueryExporter;
use App\Models\LoanApplication;
use App\Models\LoanEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BigQueryExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_streams_an_event_with_the_expected_row_and_bearer_token(): void
    {
        Http::fake([
            'https://bigquery.googleapis.com/*' => Http::response(['kind' => 'bigquery#tableDataInsertAllResponse']),
        ]);

        $application = LoanApplication::factory()->create();
        $event = $this->eventFor($application);
        $exporter = $this->exporter();

        $exporter->export($event);

        $this->assertNotNull($event->refresh()->bigquery_exported_at);

        Http::assertSent(function (ClientRequest $request) use ($application, $event): bool {
            $body = $request->data();

            return $request->method() === 'POST'
                && str_contains($request->url(), '/projects/project-id/datasets/analytics/tables/loan_events/insertAll')
                && $request->hasHeader('Authorization', 'Bearer fake-token')
                && $body['rows'][0]['insertId'] === $event->id
                && $body['rows'][0]['json']['application_reference'] === $application->reference
                && $body['rows'][0]['json']['event_type'] === 'loan_application.received';
        });
    }

    public function test_it_throws_when_bigquery_rejects_the_row(): void
    {
        Http::fake([
            'https://bigquery.googleapis.com/*' => Http::response(['error' => 'bad schema'], 400),
        ]);

        $event = $this->eventFor(LoanApplication::factory()->create());

        $this->expectException(IntegrationException::class);

        $this->exporter()->export($event);
    }

    private function exporter(): BigQueryExporter
    {
        $tokens = new class implements AccessTokenProvider
        {
            public function token(): string
            {
                return 'fake-token';
            }
        };

        return new BigQueryExporter(app(Factory::class), $tokens, [
            'project_id' => 'project-id',
            'dataset' => 'analytics',
            'table' => 'loan_events',
            'api_url' => 'https://bigquery.googleapis.com/bigquery/v2',
            'timeout_seconds' => 5,
        ]);
    }

    private function eventFor(LoanApplication $application): LoanEvent
    {
        return $application->events()->create([
            'sequence' => 1,
            'type' => 'loan_application.received',
            'payload' => ['source' => 'test'],
            'previous_hash' => null,
            'hash' => str_repeat('a', 64),
            'occurred_at' => now(),
        ]);
    }
}

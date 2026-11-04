<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthAndMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_health_endpoint_reports_database_and_cache_status(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', true)
            ->assertJsonPath('checks.cache', true);
    }

    public function test_metrics_require_a_bearer_token(): void
    {
        config(['loanflow.metrics.token' => 'metrics-secret']);

        $this->getJson('/api/v1/metrics')->assertUnauthorized();
    }

    public function test_metrics_expose_prometheus_series(): void
    {
        config(['loanflow.metrics.token' => 'metrics-secret']);

        $response = $this->withHeader('Authorization', 'Bearer metrics-secret')
            ->get('/api/v1/metrics');

        $response->assertOk();
        $this->assertStringContainsString('loanflow_applications_total', $response->getContent());
        $this->assertStringContainsString('loanflow_outbox_pending', $response->getContent());
        $this->assertStringContainsString('text/plain', (string) $response->headers->get('Content-Type'));
    }
}

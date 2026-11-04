<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LoanApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GtmEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['loanflow.gtm.ingest_secret' => 'test-gtm-secret']);
    }

    public function test_a_valid_signed_event_is_recorded(): void
    {
        $application = LoanApplication::factory()->create();

        $payload = [
            'event' => 'loan_application_started',
            'application_reference' => $application->reference,
            'parameters' => ['step' => 'details', 'variant' => 'a'],
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $raw, 'test-gtm-secret');

        $this->call(
            'POST',
            '/api/v1/events/gtm',
            [],
            [],
            [],
            ['HTTP_X-Loanflow-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $raw,
        )->assertAccepted();

        $this->assertDatabaseHas('loan_events', [
            'loan_application_id' => $application->id,
            'type' => 'gtm.loan_application_started',
        ]);
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        $application = LoanApplication::factory()->create();

        $payload = [
            'event' => 'loan_application_started',
            'application_reference' => $application->reference,
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/api/v1/events/gtm',
            [],
            [],
            [],
            ['HTTP_X-Loanflow-Signature' => 'wrong', 'CONTENT_TYPE' => 'application/json'],
            $raw,
        )->assertUnauthorized();
    }

    public function test_unknown_events_are_rejected(): void
    {
        $application = LoanApplication::factory()->create();

        $payload = [
            'event' => 'purchase',
            'application_reference' => $application->reference,
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $raw, 'test-gtm-secret');

        $this->call(
            'POST',
            '/api/v1/events/gtm',
            [],
            [],
            [],
            ['HTTP_X-Loanflow-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $raw,
        )->assertStatus(422);
    }

    public function test_an_unknown_application_reference_returns_not_found(): void
    {
        $payload = [
            'event' => 'loan_application_viewed',
            'application_reference' => 'LF-2026-DOESNOTEX',
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $raw, 'test-gtm-secret');

        $this->call(
            'POST',
            '/api/v1/events/gtm',
            [],
            [],
            [],
            ['HTTP_X-Loanflow-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $raw,
        )->assertNotFound();
    }
}

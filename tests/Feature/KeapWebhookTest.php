<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LoanApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeapWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['loanflow.keap.webhook_secret' => 'test-keap-secret']);
    }

    public function test_an_invalid_signature_is_rejected_and_recorded(): void
    {
        $application = LoanApplication::factory()->create(['status' => LoanApplication::STATUS_SCREENING]);

        $payload = [
            'id' => 'evt-invalid-1',
            'contact_id' => $application->keap_contact_id ?? 'missing',
            'loan_status' => 'approved',
        ];

        $this->call(
            'POST',
            '/api/v1/webhooks/keap',
            [],
            [],
            [],
            ['HTTP_X-Keap-Signature' => 'wrong', 'CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        )->assertUnauthorized();

        $this->assertDatabaseHas('webhook_receipts', [
            'provider' => 'keap',
            'event_id' => 'evt-invalid-1',
            'signature_valid' => false,
        ]);
        $this->assertSame(LoanApplication::STATUS_SCREENING, $application->refresh()->status);
    }

    public function test_a_valid_webhook_updates_the_application_and_is_idempotent(): void
    {
        $application = LoanApplication::factory()->create([
            'status' => LoanApplication::STATUS_SCREENING,
            'keap_contact_id' => 'contact-123',
        ]);

        $payload = [
            'id' => 'evt-valid-1',
            'contact_id' => 'contact-123',
            'loan_status' => 'approved',
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $raw, 'test-keap-secret');

        $this->call(
            'POST',
            '/api/v1/webhooks/keap',
            [],
            [],
            [],
            ['HTTP_X-Keap-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $raw,
        )->assertAccepted();

        $this->assertSame(LoanApplication::STATUS_APPROVED, $application->refresh()->status);
        $this->assertDatabaseHas('webhook_receipts', [
            'provider' => 'keap',
            'event_id' => 'evt-valid-1',
            'signature_valid' => true,
            'status' => 'processed',
        ]);

        $this->call(
            'POST',
            '/api/v1/webhooks/keap',
            [],
            [],
            [],
            ['HTTP_X-Keap-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $raw,
        )->assertAccepted();

        $this->assertDatabaseCount('webhook_receipts', 1);
    }

    public function test_a_webhook_can_resolve_an_application_by_loan_reference(): void
    {
        $application = LoanApplication::factory()->create(['status' => LoanApplication::STATUS_SCREENING]);

        $payload = [
            'id' => 'evt-reference-1',
            'loan_reference' => $application->reference,
            'loan_status' => 'declined',
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $raw, 'test-keap-secret');

        $this->call(
            'POST',
            '/api/v1/webhooks/keap',
            [],
            [],
            [],
            ['HTTP_X-Keap-Signature' => 'sha256='.$signature, 'CONTENT_TYPE' => 'application/json'],
            $raw,
        )->assertAccepted();

        $this->assertSame(LoanApplication::STATUS_DECLINED, $application->refresh()->status);
    }
}

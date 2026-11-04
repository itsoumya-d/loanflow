<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessLoanApplication;
use App\Models\LoanApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LoanApplicationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->postJson('/api/v1/loan-applications', $this->payload())
            ->assertUnauthorized();
    }

    public function test_idempotency_key_is_required(): void
    {
        $this->actingAsApiClient(['applications:write']);

        $this->postJson('/api/v1/loan-applications', $this->payload())
            ->assertStatus(400)
            ->assertJsonPath('message', 'A valid Idempotency-Key header (8-128 characters) is required.');
    }

    public function test_a_valid_application_is_created_with_an_event_and_audit_log(): void
    {
        Queue::fake();
        $this->actingAsApiClient(['applications:write']);

        $response = $this->withHeader('Idempotency-Key', 'test-key-12345')
            ->postJson('/api/v1/loan-applications', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.status', LoanApplication::STATUS_RECEIVED)
            ->assertJsonPath('data.loan_amount', 25000)
            ->assertJsonStructure(['data' => ['reference', 'status', 'submitted_at']]);

        $this->assertDatabaseCount('loan_applications', 1);
        $this->assertDatabaseHas('loan_events', ['type' => 'loan_application.received']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'loan_application.submitted']);
        Queue::assertPushed(ProcessLoanApplication::class);

        $application = LoanApplication::query()->firstOrFail();
        $this->assertNotSame('+64211234567', $application->getRawOriginal('phone'));
        $this->assertSame('+64211234567', $application->phone);
    }

    public function test_replaying_the_same_key_and_payload_returns_the_existing_application(): void
    {
        Queue::fake();
        $this->actingAsApiClient(['applications:write']);
        $payload = $this->payload();

        $first = $this->withHeader('Idempotency-Key', 'replay-key-12345')
            ->postJson('/api/v1/loan-applications', $payload)
            ->assertCreated();

        $second = $this->withHeader('Idempotency-Key', 'replay-key-12345')
            ->postJson('/api/v1/loan-applications', $payload)
            ->assertOk();

        $this->assertSame(
            $first->json('data.reference'),
            $second->json('data.reference'),
        );
        $this->assertDatabaseCount('loan_applications', 1);
        Queue::assertPushed(ProcessLoanApplication::class, 1);
    }

    public function test_reusing_a_key_with_a_different_payload_returns_a_conflict(): void
    {
        Queue::fake();
        $this->actingAsApiClient(['applications:write']);

        $this->withHeader('Idempotency-Key', 'conflict-key-12345')
            ->postJson('/api/v1/loan-applications', $this->payload())
            ->assertCreated();

        $this->withHeader('Idempotency-Key', 'conflict-key-12345')
            ->postJson('/api/v1/loan-applications', $this->payload(['loan_amount_cents' => 3_000_000]))
            ->assertStatus(409);

        $this->assertDatabaseCount('loan_applications', 1);
    }

    public function test_consent_and_validation_rules_are_enforced(): void
    {
        Queue::fake();
        $this->actingAsApiClient(['applications:write']);

        $this->withHeader('Idempotency-Key', 'validation-key-12345')
            ->postJson('/api/v1/loan-applications', $this->payload([
                'credit_check_consent' => false,
                'email' => 'not-an-email',
                'loan_amount_cents' => 1,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['credit_check_consent', 'email', 'loan_amount_cents']);
    }

    public function test_a_client_without_the_write_scope_is_forbidden(): void
    {
        $this->actingAsApiClient([]);

        $this->withHeader('Idempotency-Key', 'scope-key-12345')
            ->postJson('/api/v1/loan-applications', $this->payload())
            ->assertForbidden();
    }

    /**
     * @param  list<string>  $scopes
     */
    private function actingAsApiClient(array $scopes): Client
    {
        $client = Client::query()->create([
            'name' => 'Test client',
            'secret' => Str::random(48),
            'redirect_uris' => [],
            'grant_types' => ['client_credentials'],
            'revoked' => false,
        ]);

        Passport::actingAsClient($client, $scopes);

        return $client;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'applicant_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+64211234567',
            'loan_amount_cents' => 2_500_000,
            'loan_purpose' => 'vehicle',
            'employment_status' => 'employed',
            'annual_income_cents' => 9_500_000,
            'credit_check_consent' => true,
            'privacy_consent' => true,
        ], $overrides);
    }
}

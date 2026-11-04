<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class OAuthTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_client_can_exchange_credentials_for_a_scoped_access_token(): void
    {
        $client = app(ClientRepository::class)->createClientCredentialsGrantClient('WordPress intake');

        $token = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'scope' => 'applications:write',
        ])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in'])
            ->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', 'oauth-key-12345')
            ->postJson('/api/v1/loan-applications', [
                'applicant_name' => 'OAuth Applicant',
                'email' => 'oauth@example.com',
                'phone' => '+64211234567',
                'loan_amount_cents' => 1_500_000,
                'loan_purpose' => 'vehicle',
                'employment_status' => 'employed',
                'annual_income_cents' => 8_000_000,
                'credit_check_consent' => true,
                'privacy_consent' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.applicant_name', 'OAuth Applicant');
    }

    public function test_invalid_client_secrets_are_rejected(): void
    {
        $client = app(ClientRepository::class)->createClientCredentialsGrantClient('WordPress intake');

        $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => 'wrong-secret',
            'scope' => 'applications:write',
        ])->assertUnauthorized();
    }
}

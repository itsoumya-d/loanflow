<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Integrations\Keap\KeapClient;
use App\Integrations\Keap\KeapTokenProvider;
use App\Models\LoanApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KeapClientTest extends TestCase
{
    use RefreshDatabase;

    private array $config;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->config = [
            'base_url' => 'https://api.infusionsoft.com',
            'token_url' => 'https://api.infusionsoft.com/token',
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'refresh_token' => null,
            'timeout_seconds' => 5,
            'retry_attempts' => 3,
            'retry_base_ms' => 1,
            'custom_fields' => [
                'loan_reference' => 10,
                'loan_amount' => 11,
                'loan_status' => 12,
            ],
        ];
    }

    public function test_it_creates_a_contact_when_no_match_exists(): void
    {
        Http::fake(function (ClientRequest $request) {
            if (str_ends_with($request->url(), '/token')) {
                return Http::response(['access_token' => 'token-1', 'expires_in' => 3600]);
            }

            if ($request->method() === 'GET') {
                return Http::response(['contacts' => []]);
            }

            return Http::response(['id' => 987]);
        });

        $application = LoanApplication::factory()->create(['applicant_name' => 'Jane Doe']);
        $client = $this->client();

        $this->assertSame('987', $client->upsertContact($application));

        Http::assertSent(function (ClientRequest $request) use ($application): bool {
            if ($request->method() !== 'POST' || ! str_ends_with($request->url(), '/crm/rest/v2/contacts')) {
                return false;
            }

            $body = $request->data();

            return $body['given_name'] === 'Jane'
                && $body['email_addresses'][0]['email'] === $application->email
                && $body['custom_fields'][0]['id'] === 10
                && $request->hasHeader('Authorization', 'Bearer token-1');
        });
    }

    public function test_it_updates_an_existing_contact(): void
    {
        Http::fake(function (ClientRequest $request) {
            if (str_ends_with($request->url(), '/token')) {
                return Http::response(['access_token' => 'token-1', 'expires_in' => 3600]);
            }

            if ($request->method() === 'GET') {
                return Http::response(['contacts' => [['id' => 42]]]);
            }

            return Http::response(['id' => 42]);
        });

        $application = LoanApplication::factory()->create();
        $client = $this->client();

        $this->assertSame('42', $client->upsertContact($application));

        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'PATCH'
            && str_ends_with($request->url(), '/crm/rest/v2/contacts/42'));
    }

    public function test_it_retries_transient_failures(): void
    {
        $attempts = 0;

        Http::fake(function (ClientRequest $request) use (&$attempts) {
            if (str_ends_with($request->url(), '/token')) {
                return Http::response(['access_token' => 'token-1', 'expires_in' => 3600]);
            }

            $attempts++;

            if ($request->method() === 'GET') {
                return $attempts === 1
                    ? Http::response(['error' => 'unavailable'], 503)
                    : Http::response(['contacts' => []]);
            }

            return Http::response(['id' => 55]);
        });

        $client = $this->client();
        $id = $client->upsertContact(LoanApplication::factory()->create());

        $this->assertSame('55', $id);
        $this->assertGreaterThanOrEqual(3, $attempts);
    }

    public function test_it_caches_the_access_token(): void
    {
        Http::fake(function (ClientRequest $request) {
            if (str_ends_with($request->url(), '/token')) {
                return Http::response(['access_token' => 'token-1', 'expires_in' => 3600]);
            }

            return Http::response(['contacts' => [['id' => 1]]]);
        });

        $client = $this->client();
        $client->upsertContact(LoanApplication::factory()->create());
        $client->upsertContact(LoanApplication::factory()->create());

        Http::assertSentCount(5);
    }

    private function client(): KeapClient
    {
        $factory = app(Factory::class);

        return new KeapClient(
            $factory,
            new KeapTokenProvider($factory, $this->config),
            $this->config,
        );
    }
}

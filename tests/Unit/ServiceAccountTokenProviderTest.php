<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Integrations\BigQuery\ServiceAccountAccessTokenProvider;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServiceAccountTokenProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_signs_a_google_jwt_and_exchanges_it_for_an_access_token(): void
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($key);

        openssl_pkey_export($key, $privateKey);

        $credentials = [
            'type' => 'service_account',
            'client_email' => 'loanflow@example.iam.gserviceaccount.com',
            'private_key_id' => 'key-id',
            'private_key' => $privateKey,
        ];

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ya29.test-token',
                'expires_in' => 3600,
            ]),
        ]);

        $provider = new ServiceAccountAccessTokenProvider(app(Factory::class), [
            'service_account_json' => json_encode($credentials, JSON_THROW_ON_ERROR),
            'token_url' => 'https://oauth2.googleapis.com/token',
            'scope' => 'https://www.googleapis.com/auth/bigquery.insertdata',
            'timeout_seconds' => 5,
        ]);

        $this->assertSame('ya29.test-token', $provider->token());

        Http::assertSent(function (ClientRequest $request): bool {
            $assertion = $request->data()['assertion'] ?? '';
            $segments = explode('.', (string) $assertion);
            $claims = json_decode(base64_decode(strtr($segments[1] ?? '', '-_', '+/'), true), true);

            return $request->method() === 'POST'
                && ($request->data()['grant_type'] ?? '') === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
                && count($segments) === 3
                && ($claims['iss'] ?? '') === 'loanflow@example.iam.gserviceaccount.com'
                && ($claims['scope'] ?? '') === 'https://www.googleapis.com/auth/bigquery.insertdata';
        });
    }
}

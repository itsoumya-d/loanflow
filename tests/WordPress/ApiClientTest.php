<?php

declare(strict_types=1);

namespace Tests\WordPress;

use Brain\Monkey\Functions;
use Loanflow_Api_Client;
use WP_Error;

class ApiClientTest extends WordPressTestCase
{
    private array $settings = [
        'api_base_url' => 'https://api.loanflow.test',
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
    ];

    public function test_it_requests_a_token_and_submits_an_application(): void
    {
        $posts = [];

        $this->stubWordPress(function (string $url, array $args) use (&$posts): array {
            return $this->fakePost($url, $args, $posts);
        });

        $client = new Loanflow_Api_Client($this->settings);
        $result = $client->submit([
            'applicant_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+64211234567',
            'loan_amount_cents' => 2_500_000,
            'loan_purpose' => 'vehicle',
            'employment_status' => 'employed',
            'annual_income_cents' => 9_500_000,
            'credit_check_consent' => true,
            'privacy_consent' => true,
        ]);

        $this->assertSame('LF-2026-ABCD1234', $result['data']['reference']);
        $this->assertCount(2, $posts);
        $this->assertStringEndsWith('/oauth/token', $posts[0]['url']);
        $this->assertSame('client_credentials', $posts[0]['args']['body']['grant_type']);
        $this->assertSame('Bearer token-1', $posts[1]['args']['headers']['Authorization']);
        $this->assertSame('wordpress', $posts[1]['args']['headers']['X-Loanflow-Source']);
        $this->assertSame('client-id', $posts[0]['args']['body']['client_id']);
    }

    public function test_it_caches_the_access_token(): void
    {
        $tokenRequests = 0;
        $cachedToken = null;

        $this->stubWordPress(function (string $url, array $args) use (&$tokenRequests): array {
            if (str_ends_with($url, '/oauth/token')) {
                $tokenRequests++;

                return [
                    'response' => ['code' => 200],
                    'body' => json_encode(['access_token' => 'token-1', 'expires_in' => 3600]),
                ];
            }

            return [
                'response' => ['code' => 201],
                'body' => json_encode(['data' => ['reference' => 'LF-2026-ABCD1234']]),
            ];
        }, $cachedToken);

        $client = new Loanflow_Api_Client($this->settings);
        $client->token();
        $client->token();

        $this->assertSame(1, $tokenRequests);
        $this->assertSame('token-1', $cachedToken);
    }

    public function test_it_returns_a_wp_error_for_failed_submissions(): void
    {
        $this->stubWordPress(function (string $url): array {
            if (str_ends_with($url, '/oauth/token')) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode(['access_token' => 'token-1', 'expires_in' => 3600]),
                ];
            }

            return [
                'response' => ['code' => 502],
                'body' => json_encode(['message' => 'Upstream failed']),
            ];
        });

        $client = new Loanflow_Api_Client($this->settings);
        $result = $client->submit([
            'applicant_name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->assertInstanceOf(WP_Error::class, $result);
    }

    private function stubWordPress(callable $postHandler, ?string &$cachedToken = null): void
    {
        Functions\when('get_option')->justReturn($this->settings);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('untrailingslashit')->alias(static fn (string $value): string => rtrim($value, '/'));
        Functions\when('wp_generate_uuid4')->justReturn('11111111-1111-4111-8111-111111111111');
        Functions\when('wp_json_encode')->alias(static fn (mixed $value): string => (string) json_encode($value));
        Functions\when('is_wp_error')->alias(static fn (mixed $value): bool => $value instanceof WP_Error);
        Functions\when('wp_remote_retrieve_response_code')->alias(static fn (array $response): int => (int) ($response['response']['code'] ?? 0));
        Functions\when('wp_remote_retrieve_body')->alias(static fn (array $response): string => (string) ($response['body'] ?? ''));
        Functions\when('get_transient')->alias(static function (string $key) use (&$cachedToken): string|false {
            return $key === 'loanflow_access_token' && $cachedToken !== null ? $cachedToken : false;
        });
        Functions\when('set_transient')->alias(static function (string $key, mixed $value, int $ttl) use (&$cachedToken): bool {
            if ($key === 'loanflow_access_token') {
                $cachedToken = (string) $value;
            }

            return true;
        });
        Functions\when('delete_transient')->justReturn(true);
        Functions\when('wp_remote_post')->alias($postHandler);
    }

    /**
     * @param  list<array{url: string, args: array<string, mixed>}>  $posts
     * @return array<string, mixed>
     */
    private function fakePost(string $url, array $args, array &$posts): array
    {
        $posts[] = ['url' => $url, 'args' => $args];

        if (str_ends_with($url, '/oauth/token')) {
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['access_token' => 'token-1', 'expires_in' => 3600]),
            ];
        }

        return [
            'response' => ['code' => 201],
            'body' => json_encode(['data' => ['reference' => 'LF-2026-ABCD1234', 'status' => 'received']]),
        ];
    }
}

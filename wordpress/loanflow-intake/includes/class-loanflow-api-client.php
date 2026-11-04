<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class Loanflow_Api_Client
{
    /** @var array<string, mixed> */
    private array $settings;

    /**
     * @param  array<string, mixed>|null  $settings
     */
    public function __construct(?array $settings = null)
    {
        $stored = get_option('loanflow_settings', []);
        $this->settings = is_array($settings) ? $settings : (is_array($stored) ? $stored : []);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|WP_Error
     */
    public function submit(array $payload): array|WP_Error
    {
        $payload['idempotency_key'] = wp_generate_uuid4();

        $token = $this->token();

        if (is_wp_error($token)) {
            return $token;
        }

        $response = $this->post('/api/v1/loan-applications', $payload, $token);
        $code = (int) wp_remote_retrieve_response_code($response);

        if ($code === 401) {
            delete_transient('loanflow_access_token');
            $token = $this->token();

            if (is_wp_error($token)) {
                return $token;
            }

            $response = $this->post('/api/v1/loan-applications', $payload, $token);
            $code = (int) wp_remote_retrieve_response_code($response);
        }

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $body = is_array($body) ? $body : [];

        if ($code < 200 || $code >= 300) {
            return new WP_Error(
                'loanflow_api_error',
                Loanflow_I18n::t('We could not submit your application. Please try again.'),
                ['status' => $code, 'body' => $body],
            );
        }

        return $body;
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public function get_application(string $reference): array|WP_Error
    {
        $token = $this->token();

        if (is_wp_error($token)) {
            return $token;
        }

        $response = wp_remote_get($this->base_url().'/api/v1/machine/loan-applications/'.rawurlencode($reference), [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $body = is_array($body) ? $body : [];

        if ((int) wp_remote_retrieve_response_code($response) !== 200) {
            return new WP_Error('loanflow_api_error', Loanflow_I18n::t('Application lookup failed.'), ['body' => $body]);
        }

        return $body;
    }

    public function token(): string|WP_Error
    {
        $cached = get_transient('loanflow_access_token');

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = wp_remote_post($this->base_url().'/oauth/token', [
            'timeout' => 15,
            'body' => [
                'grant_type' => 'client_credentials',
                'client_id' => (string) ($this->settings['client_id'] ?? ''),
                'client_secret' => (string) ($this->settings['client_secret'] ?? ''),
                'scope' => 'applications:write applications:read',
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $body = is_array($body) ? $body : [];

        if ((int) wp_remote_retrieve_response_code($response) !== 200 || empty($body['access_token'])) {
            return new WP_Error('loanflow_token_error', Loanflow_I18n::t('Could not authenticate with the loan service.'), ['body' => $body]);
        }

        $token = (string) $body['access_token'];
        $ttl = max(60, (int) ($body['expires_in'] ?? 3600) - 60);
        set_transient('loanflow_access_token', $token, $ttl);

        return $token;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|WP_Error
     */
    private function post(string $path, array $payload, string $token): array|WP_Error
    {
        $response = wp_remote_post($this->base_url().$path, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Idempotency-Key' => (string) $payload['idempotency_key'],
                'X-Loanflow-Source' => 'wordpress',
            ],
            'body' => wp_json_encode($payload),
        ]);

        return $response;
    }

    private function base_url(): string
    {
        return untrailingslashit((string) ($this->settings['api_base_url'] ?? ''));
    }
}

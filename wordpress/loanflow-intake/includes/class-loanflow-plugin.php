<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class Loanflow_Plugin
{
    private static ?self $instance = null;

    private Loanflow_Form $form;

    private Loanflow_Gtm $gtm;

    private function __construct()
    {
        $this->form = new Loanflow_Form;
        $this->gtm = new Loanflow_Gtm;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function init(): void
    {
        add_shortcode('loanflow_application_form', [$this->form, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function maybe_enqueue_assets(): void
    {
        if (! is_singular()) {
            return;
        }

        $post = get_post();

        if (! $post instanceof WP_Post || ! has_shortcode((string) $post->post_content, 'loanflow_application_form')) {
            return;
        }

        wp_enqueue_style(
            'loanflow-intake',
            LOANFLOW_INTAKE_URL.'assets/css/loanflow.css',
            [],
            LOANFLOW_INTAKE_VERSION,
        );

        wp_enqueue_script(
            'loanflow-intake',
            LOANFLOW_INTAKE_URL.'assets/js/loanflow.js',
            [],
            LOANFLOW_INTAKE_VERSION,
            true,
        );

        $settings = get_option('loanflow_settings', []);
        $settings = is_array($settings) ? $settings : [];

        wp_localize_script('loanflow-intake', 'loanflowIntake', [
            'restUrl' => Loanflow_I18n::url(rest_url('loanflow/v1/applications')),
            'nonce' => wp_create_nonce('loanflow_form'),
            'gtm' => $this->gtm->bootstrap($settings),
            'messages' => [
                'submitting' => Loanflow_I18n::t('Submitting your application…'),
                'success' => Loanflow_I18n::t('Thank you. Your application reference is'),
                'error' => Loanflow_I18n::t('Something went wrong. Please try again.'),
                'validation' => Loanflow_I18n::t('Please check the highlighted fields.'),
            ],
        ]);

        wp_add_inline_script('loanflow-intake', 'window.dataLayer = window.dataLayer || [];', 'before');
    }

    public function register_routes(): void
    {
        register_rest_route('loanflow/v1', '/applications', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_submission'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_submission(WP_REST_Request $request): WP_REST_Response
    {
        $nonce = (string) $request->get_header('X-Loanflow-Nonce');

        if (! wp_verify_nonce($nonce, 'loanflow_form')) {
            return new WP_REST_Response(['message' => Loanflow_I18n::t('Invalid form token. Please refresh and try again.')], 403);
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR'])) : 'unknown';
        $rateKey = 'loanflow_rl_'.md5($ip);
        $count = (int) get_transient($rateKey);

        if ($count >= 5) {
            return new WP_REST_Response(['message' => Loanflow_I18n::t('Too many submissions. Please try again shortly.')], 429);
        }

        set_transient($rateKey, $count + 1, MINUTE_IN_SECONDS);

        $raw = $request->get_json_params();
        $raw = is_array($raw) ? $raw : $request->get_params();
        $data = $this->form->sanitize(is_array($raw) ? $raw : []);
        $errors = $this->form->validate($data);

        if ($errors !== []) {
            return new WP_REST_Response(['message' => Loanflow_I18n::t('Please check the form.'), 'errors' => $errors], 422);
        }

        $client = new Loanflow_Api_Client;
        $result = $client->submit([
            'applicant_name' => $data['applicant_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'loan_amount_cents' => $data['loan_amount_cents'],
            'loan_purpose' => $data['loan_purpose'],
            'employment_status' => $data['employment_status'],
            'annual_income_cents' => $data['annual_income_cents'],
            'credit_check_consent' => $data['credit_check_consent'],
            'privacy_consent' => $data['privacy_consent'],
            'metadata' => [
                'source' => 'wordpress',
                'notes' => $data['notes'],
                'page_url' => Loanflow_I18n::url((string) $request->get_header('Referer')),
            ],
        ]);

        if (is_wp_error($result)) {
            return new WP_REST_Response(['message' => $result->get_error_message()], 502);
        }

        $reference = (string) ($result['data']['reference'] ?? '');
        $response = new WP_REST_Response([
            'reference' => $reference,
            'status' => (string) ($result['data']['status'] ?? 'received'),
        ], 201);

        if ($reference !== '') {
            $response->header('X-Loanflow-Reference', $reference);
        }

        return $response;
    }

    public function register_settings_page(): void
    {
        add_options_page(
            Loanflow_I18n::t('Loanflow Intake'),
            Loanflow_I18n::t('Loanflow Intake'),
            'manage_options',
            'loanflow-intake',
            [$this, 'render_settings_page'],
        );
    }

    public function register_settings(): void
    {
        register_setting('loanflow_intake', 'loanflow_settings', [
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default' => [
                'api_base_url' => '',
                'client_id' => '',
                'client_secret' => '',
                'gtm_enabled' => '1',
            ],
        ]);

        add_settings_section('loanflow_main', Loanflow_I18n::t('Connection'), static function (): void {
            echo '<p>'.Loanflow_I18n::t('Connect this site to the Loanflow API using an OAuth 2.0 client credentials grant.').'</p>';
        }, 'loanflow-intake');

        add_settings_field('loanflow_api_base_url', Loanflow_I18n::t('API base URL'), [$this, 'render_text_field'], 'loanflow-intake', 'loanflow_main', ['key' => 'api_base_url', 'type' => 'url']);
        add_settings_field('loanflow_client_id', Loanflow_I18n::t('OAuth client ID'), [$this, 'render_text_field'], 'loanflow-intake', 'loanflow_main', ['key' => 'client_id', 'type' => 'text']);
        add_settings_field('loanflow_client_secret', Loanflow_I18n::t('OAuth client secret'), [$this, 'render_text_field'], 'loanflow-intake', 'loanflow_main', ['key' => 'client_secret', 'type' => 'password']);
        add_settings_field('loanflow_gtm_enabled', Loanflow_I18n::t('GTM events'), [$this, 'render_checkbox_field'], 'loanflow-intake', 'loanflow_main', ['key' => 'gtm_enabled']);
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function render_text_field(array $args): void
    {
        $settings = get_option('loanflow_settings', []);
        $settings = is_array($settings) ? $settings : [];
        $key = (string) ($args['key'] ?? '');
        $type = (string) ($args['type'] ?? 'text');
        $value = (string) ($settings[$key] ?? '');

        printf(
            '<input type="%s" name="loanflow_settings[%s]" value="%s" class="regular-text" autocomplete="off">',
            Loanflow_I18n::attr($type),
            Loanflow_I18n::attr($key),
            Loanflow_I18n::attr($value),
        );
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function render_checkbox_field(array $args): void
    {
        $settings = get_option('loanflow_settings', []);
        $settings = is_array($settings) ? $settings : [];
        $key = (string) ($args['key'] ?? '');
        $checked = (string) ($settings[$key] ?? '1') === '1';

        printf(
            '<label><input type="checkbox" name="loanflow_settings[%s]" value="1" %s> %s</label>',
            Loanflow_I18n::attr($key),
            checked($checked, true, false),
            Loanflow_I18n::t('Push consent-aware events to the GTM dataLayer.'),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function sanitize_settings(array $input): array
    {
        return [
            'api_base_url' => Loanflow_I18n::url((string) ($input['api_base_url'] ?? '')),
            'client_id' => sanitize_text_field((string) ($input['client_id'] ?? '')),
            'client_secret' => sanitize_text_field((string) ($input['client_secret'] ?? '')),
            'gtm_enabled' => ! empty($input['gtm_enabled']) ? '1' : '0',
        ];
    }

    public function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo Loanflow_I18n::t('Loanflow Intake'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('loanflow_intake');
        do_settings_sections('loanflow-intake');
        submit_button();
        ?>
            </form>
            <p><?php echo Loanflow_I18n::t('Shortcode:'); ?> <code>[loanflow_application_form]</code></p>
        </div>
        <?php
    }
}

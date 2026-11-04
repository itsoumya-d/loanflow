<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class Loanflow_Gtm
{
    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function data_layer_event(string $event, array $parameters = []): array
    {
        return [
            'event' => $event,
            'loanflow' => array_merge([
                'plugin_version' => defined('LOANFLOW_INTAKE_VERSION') ? LOANFLOW_INTAKE_VERSION : '1.0.0',
            ], $parameters),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function is_enabled(array $settings): bool
    {
        return (string) ($settings['gtm_enabled'] ?? '1') === '1';
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function bootstrap(array $settings): array
    {
        return [
            'enabled' => $this->is_enabled($settings),
            'consent' => [
                'analytics_storage' => 'denied',
                'ad_storage' => 'denied',
                'ad_user_data' => 'denied',
                'ad_personalization' => 'denied',
            ],
            'events' => [
                'form_viewed' => 'loan_application_viewed',
                'form_started' => 'loan_application_started',
                'form_submitted' => 'loan_application_submitted',
                'validation_failed' => 'loan_application_validation_failed',
            ],
        ];
    }
}

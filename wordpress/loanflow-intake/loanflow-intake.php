<?php

/**
 * Plugin Name:       Loanflow Intake
 * Plugin URI:        https://github.com/itsoumya-d/loanflow
 * Description:       Accessible loan application intake for WordPress with OAuth 2.0, GTM dataLayer events and server-side validation.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.2
 * Author:            Soumya Debnath
 * License:           MIT
 * Text Domain:       loanflow-intake
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('LOANFLOW_INTAKE_VERSION', '1.0.0');
define('LOANFLOW_INTAKE_FILE', __FILE__);
define('LOANFLOW_INTAKE_DIR', plugin_dir_path(__FILE__));
define('LOANFLOW_INTAKE_URL', plugin_dir_url(__FILE__));

require_once LOANFLOW_INTAKE_DIR.'includes/class-loanflow-i18n.php';
require_once LOANFLOW_INTAKE_DIR.'includes/class-loanflow-form.php';
require_once LOANFLOW_INTAKE_DIR.'includes/class-loanflow-api-client.php';
require_once LOANFLOW_INTAKE_DIR.'includes/class-loanflow-gtm.php';
require_once LOANFLOW_INTAKE_DIR.'includes/class-loanflow-plugin.php';

add_action('plugins_loaded', static function (): void {
    Loanflow_Plugin::instance()->init();
});

register_activation_hook(__FILE__, static function (): void {
    if (get_option('loanflow_settings') === false) {
        add_option('loanflow_settings', [
            'api_base_url' => '',
            'client_id' => '',
            'client_secret' => '',
            'gtm_enabled' => '1',
        ]);
    }
});

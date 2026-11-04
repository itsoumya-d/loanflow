<?php

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('loanflow_settings');
delete_transient('loanflow_access_token');

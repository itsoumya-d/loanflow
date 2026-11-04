<?php

declare(strict_types=1);

namespace Tests\WordPress;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/wp-stubs.php';

abstract class WordPressTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Monkey\setUp();

        if (! defined('ABSPATH')) {
            define('ABSPATH', __DIR__.'/');
        }

        if (! defined('LOANFLOW_INTAKE_VERSION')) {
            define('LOANFLOW_INTAKE_VERSION', '1.0.0');
        }

        if (! defined('LOANFLOW_INTAKE_URL')) {
            define('LOANFLOW_INTAKE_URL', 'https://example.test/wp-content/plugins/loanflow-intake/');
        }

        if (! defined('MINUTE_IN_SECONDS')) {
            define('MINUTE_IN_SECONDS', 60);
        }

        require_once dirname(__DIR__, 2).'/wordpress/loanflow-intake/includes/class-loanflow-i18n.php';
        require_once dirname(__DIR__, 2).'/wordpress/loanflow-intake/includes/class-loanflow-form.php';
        require_once dirname(__DIR__, 2).'/wordpress/loanflow-intake/includes/class-loanflow-api-client.php';
        require_once dirname(__DIR__, 2).'/wordpress/loanflow-intake/includes/class-loanflow-gtm.php';
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();

        parent::tearDown();
    }
}

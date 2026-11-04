<?php

declare(strict_types=1);

namespace Tests\WordPress;

use Loanflow_Gtm;

class GtmTest extends WordPressTestCase
{
    public function test_it_builds_a_data_layer_event_without_pii(): void
    {
        $gtm = new Loanflow_Gtm;
        $event = $gtm->data_layer_event('loan_application_submitted', [
            'application_reference' => 'LF-2026-ABCD1234',
            'step' => 'complete',
        ]);

        $this->assertSame('loan_application_submitted', $event['event']);
        $this->assertSame('LF-2026-ABCD1234', $event['loanflow']['application_reference']);
        $this->assertArrayNotHasKey('email', $event['loanflow']);
        $this->assertArrayNotHasKey('phone', $event['loanflow']);
    }

    public function test_it_respects_the_gtm_toggle(): void
    {
        $gtm = new Loanflow_Gtm;

        $this->assertTrue($gtm->is_enabled(['gtm_enabled' => '1']));
        $this->assertFalse($gtm->is_enabled(['gtm_enabled' => '0']));
    }

    public function test_it_returns_consent_denied_by_default(): void
    {
        $gtm = new Loanflow_Gtm;
        $bootstrap = $gtm->bootstrap(['gtm_enabled' => '1']);

        $this->assertSame('denied', $bootstrap['consent']['analytics_storage']);
        $this->assertSame('denied', $bootstrap['consent']['ad_storage']);
        $this->assertSame('loan_application_submitted', $bootstrap['events']['form_submitted']);
    }
}

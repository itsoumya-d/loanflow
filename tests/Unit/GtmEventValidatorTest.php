<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\InvalidGtmEventException;
use App\Integrations\Gtm\GtmEventValidator;
use Illuminate\Support\Str;
use Tests\TestCase;

class GtmEventValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['loanflow.gtm.allowed_events' => [
            'loan_application_viewed',
            'loan_application_started',
            'loan_application_submitted',
        ]]);
    }

    public function test_it_normalizes_a_valid_event(): void
    {
        $validator = new GtmEventValidator;

        $result = $validator->validate([
            'event' => 'loan_application_started',
            'application_reference' => 'LF-2026-ABCD1234',
            'parameters' => ['step' => 'details', 'attempt' => 1],
        ]);

        $this->assertSame('loan_application_started', $result['event']);
        $this->assertSame('LF-2026-ABCD1234', $result['application_reference']);
        $this->assertSame('details', $result['parameters']['step']);
        $this->assertTrue(Str::isUuid($result['event_id']));
    }

    public function test_it_rejects_unknown_events(): void
    {
        $this->expectException(InvalidGtmEventException::class);

        (new GtmEventValidator)->validate([
            'event' => 'purchase',
            'application_reference' => 'LF-2026-ABCD1234',
        ]);
    }

    public function test_it_rejects_invalid_parameter_keys(): void
    {
        $this->expectException(InvalidGtmEventException::class);

        (new GtmEventValidator)->validate([
            'event' => 'loan_application_started',
            'application_reference' => 'LF-2026-ABCD1234',
            'parameters' => ['Bad-Key' => 'value'],
        ]);
    }

    public function test_it_rejects_non_scalar_parameter_values(): void
    {
        $this->expectException(InvalidGtmEventException::class);

        (new GtmEventValidator)->validate([
            'event' => 'loan_application_started',
            'application_reference' => 'LF-2026-ABCD1234',
            'parameters' => ['nested' => ['no' => 'thanks']],
        ]);
    }
}

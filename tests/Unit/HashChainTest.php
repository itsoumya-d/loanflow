<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\LoanApplication;
use App\Services\Lending\AuditLogger;
use App\Services\Lending\LoanEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HashChainTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_chain_verifies_and_detects_tampering(): void
    {
        $application = LoanApplication::factory()->create();
        $service = app(LoanEventService::class);

        $first = $service->record($application, 'loan_application.received', ['amount' => 100]);
        $service->record($application, 'loan_application.status_changed', ['from' => 'received', 'to' => 'screening']);

        $this->assertTrue($service->verifyChain($application->refresh()));

        $first->forceFill(['payload' => ['amount' => 999]])->save();

        $this->assertFalse($service->verifyChain($application->refresh()));
    }

    public function test_audit_chain_verifies_and_detects_tampering(): void
    {
        $application = LoanApplication::factory()->create();
        $logger = app(AuditLogger::class);

        $first = $logger->record('loan_application.submitted', $application, ['source' => 'test']);
        $logger->record('loan_application.viewed', $application, ['viewer' => 'officer']);

        $this->assertTrue($logger->verifyChain());

        $first->forceFill(['metadata' => ['source' => 'tampered']])->save();

        $this->assertFalse($logger->verifyChain());
    }
}

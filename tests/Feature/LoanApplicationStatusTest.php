<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LoanApplication;
use App\Models\User;
use App\Services\Lending\LoanEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LoanApplicationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_loan_officer_can_move_an_application_through_the_workflow(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_LOAN_OFFICER]);
        Passport::actingAs($user, ['applications:manage']);

        $application = LoanApplication::factory()->create();

        $this->patchJson("/api/v1/loan-applications/{$application->reference}/status", [
            'status' => LoanApplication::STATUS_SCREENING,
            'reason' => 'Initial screening complete.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', LoanApplication::STATUS_SCREENING);

        $this->assertDatabaseHas('loan_events', [
            'loan_application_id' => $application->id,
            'type' => 'loan_application.status_changed',
        ]);
    }

    public function test_a_viewer_cannot_manage_application_status(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_VIEWER]);
        Passport::actingAs($user, ['applications:manage']);

        $application = LoanApplication::factory()->create();

        $this->patchJson("/api/v1/loan-applications/{$application->reference}/status", [
            'status' => LoanApplication::STATUS_SCREENING,
        ])->assertForbidden();
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Passport::actingAs($user, ['applications:manage']);

        $application = LoanApplication::factory()->approved()->create();

        $this->patchJson("/api/v1/loan-applications/{$application->reference}/status", [
            'status' => LoanApplication::STATUS_DECLINED,
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot transition a loan application from [approved] to [declined].');
    }

    public function test_an_admin_with_the_manage_scope_can_approve_an_application(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Passport::actingAs($user, ['applications:manage']);

        $application = LoanApplication::factory()->create(['status' => LoanApplication::STATUS_SCREENING]);

        $this->patchJson("/api/v1/loan-applications/{$application->reference}/status", [
            'status' => LoanApplication::STATUS_APPROVED,
            'reason' => 'Automated policy pass.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', LoanApplication::STATUS_APPROVED);
    }

    public function test_the_timeline_returns_the_hash_chained_event_history(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_VIEWER]);
        Passport::actingAs($user, ['applications:read']);

        $application = LoanApplication::factory()->create();
        app(LoanEventService::class)->record($application, 'loan_application.received', ['source' => 'test']);

        $this->getJson("/api/v1/loan-applications/{$application->reference}/timeline")
            ->assertOk()
            ->assertJsonPath('data.0.type', 'loan_application.received')
            ->assertJsonPath('data.0.payload.source', 'test')
            ->assertJsonStructure(['data' => [['id', 'type', 'occurred_at', 'hash']]]);
    }

    public function test_a_human_can_read_an_application_with_the_read_scope(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_VIEWER]);
        Passport::actingAs($user, ['applications:read']);

        $application = LoanApplication::factory()->create();

        $this->getJson("/api/v1/loan-applications/{$application->reference}")
            ->assertOk()
            ->assertJsonPath('data.reference', $application->reference)
            ->assertJsonPath('data.applicant_name', $application->applicant_name);
    }
}

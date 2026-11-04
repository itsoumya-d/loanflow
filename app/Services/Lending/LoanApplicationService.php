<?php

declare(strict_types=1);

namespace App\Services\Lending;

use App\Exceptions\IdempotencyConflictException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Jobs\ProcessLoanApplication;
use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LoanApplicationService
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        LoanApplication::STATUS_RECEIVED => [
            LoanApplication::STATUS_SCREENING,
            LoanApplication::STATUS_DECLINED,
            LoanApplication::STATUS_REFERRED,
        ],
        LoanApplication::STATUS_SCREENING => [
            LoanApplication::STATUS_APPROVED,
            LoanApplication::STATUS_DECLINED,
            LoanApplication::STATUS_REFERRED,
        ],
        LoanApplication::STATUS_REFERRED => [
            LoanApplication::STATUS_APPROVED,
            LoanApplication::STATUS_DECLINED,
        ],
        LoanApplication::STATUS_APPROVED => [],
        LoanApplication::STATUS_DECLINED => [],
    ];

    public function __construct(
        private readonly ApplicationReferenceGenerator $references,
        private readonly LoanEventService $events,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(array $data, string $idempotencyKey, string $source = 'api'): LoanApplicationResult
    {
        $payloadHash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $email = mb_strtolower((string) $data['email']);

        return DB::transaction(function () use ($data, $idempotencyKey, $source, $payloadHash, $email): LoanApplicationResult {
            $existing = LoanApplication::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if (! hash_equals((string) $existing->payload_hash, $payloadHash)) {
                    throw new IdempotencyConflictException;
                }

                return new LoanApplicationResult($existing, false);
            }

            $application = LoanApplication::query()->create([
                'reference' => $this->references->generate(),
                'applicant_name' => $data['applicant_name'],
                'email' => $email,
                'email_hash' => hash('sha256', $email),
                'phone' => $data['phone'],
                'loan_amount_cents' => $data['loan_amount_cents'],
                'loan_purpose' => $data['loan_purpose'],
                'employment_status' => $data['employment_status'],
                'annual_income_cents' => $data['annual_income_cents'],
                'credit_check_consent' => $data['credit_check_consent'],
                'privacy_consent' => $data['privacy_consent'],
                'status' => LoanApplication::STATUS_RECEIVED,
                'source' => $source,
                'idempotency_key' => $idempotencyKey,
                'payload_hash' => $payloadHash,
                'metadata' => $data['metadata'] ?? null,
                'submitted_at' => now(),
            ]);

            $this->events->record($application, 'loan_application.received', [
                'loan_amount_cents' => $application->loan_amount_cents,
                'loan_purpose' => $application->loan_purpose,
                'employment_status' => $application->employment_status,
                'source' => $source,
            ]);

            $this->audit->record('loan_application.submitted', $application, [
                'reference' => $application->reference,
                'source' => $source,
            ]);

            ProcessLoanApplication::dispatch($application->getKey())->afterCommit();

            return new LoanApplicationResult($application->refresh(), true);
        });
    }

    public function transition(
        LoanApplication $application,
        string $to,
        ?string $reason = null,
        ?User $actor = null,
    ): LoanApplication {
        $from = $application->status;
        $allowed = self::TRANSITIONS[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw InvalidStatusTransitionException::between($from, $to);
        }

        return DB::transaction(function () use ($application, $from, $to, $reason, $actor): LoanApplication {
            $application->forceFill([
                'status' => $to,
                'decision_at' => in_array($to, [LoanApplication::STATUS_APPROVED, LoanApplication::STATUS_DECLINED], true)
                    ? now()
                    : $application->decision_at,
            ])->save();

            $this->events->record($application, 'loan_application.status_changed', [
                'from' => $from,
                'to' => $to,
                'reason' => $reason,
            ]);

            $this->audit->record('loan_application.status_changed', $application, [
                'from' => $from,
                'to' => $to,
                'reason' => $reason,
            ], $actor);

            return $application->refresh();
        });
    }

    public function findByReference(string $reference): LoanApplication
    {
        return LoanApplication::query()->where('reference', $reference)->firstOrFail();
    }
}

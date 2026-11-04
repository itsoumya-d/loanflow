<?php

declare(strict_types=1);

namespace App\Services\Lending;

use App\Models\LoanApplication;
use App\Models\WebhookReceipt;

class KeapWebhookProcessor
{
    public function __construct(
        private readonly LoanApplicationService $applications,
        private readonly LoanEventService $events,
        private readonly WebhookReceiptService $receipts,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function process(WebhookReceipt $receipt, array $payload): void
    {
        try {
            $application = $this->findApplication($payload);

            if ($application === null) {
                $this->receipts->markProcessed($receipt);

                return;
            }

            $this->events->record($application, 'keap.webhook_received', [
                'event_id' => $receipt->event_id,
                'event_key' => $payload['event_key'] ?? null,
            ]);

            $target = $this->targetStatus($payload);

            if ($target !== null && $target !== $application->status) {
                $this->applications->transition($application, $target, 'Keap webhook');
            }

            $this->receipts->markProcessed($receipt);
        } catch (\Throwable $exception) {
            $this->receipts->markFailed($receipt, $exception->getMessage());

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function findApplication(array $payload): ?LoanApplication
    {
        $contactId = data_get($payload, 'contact_id')
            ?? data_get($payload, 'object.id')
            ?? data_get($payload, 'data.contact_id');

        if (is_string($contactId) && $contactId !== '') {
            $application = LoanApplication::query()->where('keap_contact_id', $contactId)->first();

            if ($application !== null) {
                return $application;
            }
        }

        $reference = data_get($payload, 'loan_reference')
            ?? data_get($payload, 'data.loan_reference')
            ?? data_get($payload, 'object.custom_fields.loan_reference');

        return is_string($reference) && $reference !== ''
            ? LoanApplication::query()->where('reference', $reference)->first()
            : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function targetStatus(array $payload): ?string
    {
        $status = data_get($payload, 'loan_status')
            ?? data_get($payload, 'data.loan_status')
            ?? data_get($payload, 'status');

        return match (is_string($status) ? mb_strtolower($status) : null) {
            'approved', 'approve', 'settled' => LoanApplication::STATUS_APPROVED,
            'declined', 'decline', 'rejected' => LoanApplication::STATUS_DECLINED,
            'referred', 'review' => LoanApplication::STATUS_REFERRED,
            default => null,
        };
    }
}

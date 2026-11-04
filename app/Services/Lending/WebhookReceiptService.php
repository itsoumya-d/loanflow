<?php

declare(strict_types=1);

namespace App\Services\Lending;

use App\Models\WebhookReceipt;

class WebhookReceiptService
{
    public function record(
        string $provider,
        ?string $eventId,
        bool $signatureValid,
        string $rawBody,
        string $status = 'received',
        ?string $error = null,
    ): WebhookReceipt {
        $payloadHash = hash('sha256', $rawBody);

        if ($eventId !== null) {
            $existing = WebhookReceipt::query()
                ->where('provider', $provider)
                ->where('event_id', $eventId)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return WebhookReceipt::query()->create([
            'provider' => $provider,
            'event_id' => $eventId,
            'signature_hash' => $provider.'-'.substr(hash('sha256', $rawBody), 0, 32),
            'signature_valid' => $signatureValid,
            'payload_hash' => $payloadHash,
            'status' => $status,
            'error' => $error,
            'received_at' => now(),
            'processed_at' => $status === 'processed' ? now() : null,
        ]);
    }

    public function markProcessed(WebhookReceipt $receipt): void
    {
        $receipt->forceFill([
            'status' => 'processed',
            'processed_at' => now(),
        ])->save();
    }

    public function markFailed(WebhookReceipt $receipt, string $error): void
    {
        $receipt->forceFill([
            'status' => 'failed',
            'error' => mb_substr($error, 0, 2000),
        ])->save();
    }
}

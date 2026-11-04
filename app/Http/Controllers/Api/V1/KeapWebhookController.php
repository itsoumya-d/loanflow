<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Integrations\Keap\KeapWebhookVerifier;
use App\Jobs\ProcessKeapWebhook;
use App\Services\Lending\WebhookReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KeapWebhookController
{
    public function __invoke(
        Request $request,
        KeapWebhookVerifier $verifier,
        WebhookReceiptService $receipts,
    ): JsonResponse {
        $raw = $request->getContent();
        $signature = $request->header((string) config('loanflow.keap.webhook_signature_header', 'X-Keap-Signature'));
        $valid = $verifier->verify($raw, is_string($signature) ? $signature : null);

        $payload = json_decode($raw, true);
        $eventId = is_array($payload) ? (string) ($payload['id'] ?? $payload['event_id'] ?? '') : '';
        $eventId = $eventId !== '' ? $eventId : null;

        $receipt = $receipts->record(
            'keap',
            $eventId,
            $valid,
            $raw,
            $valid ? 'received' : 'rejected',
            $valid ? null : 'Invalid signature',
        );

        if (! $valid) {
            return response()->json(['message' => 'Invalid signature.', 'receipt_id' => $receipt->id], 401);
        }

        if (! is_array($payload)) {
            return response()->json(['message' => 'Invalid JSON payload.', 'receipt_id' => $receipt->id], 422);
        }

        ProcessKeapWebhook::dispatch($receipt->id, $payload);

        return response()->json(['status' => 'accepted', 'receipt_id' => $receipt->id], 202);
    }
}

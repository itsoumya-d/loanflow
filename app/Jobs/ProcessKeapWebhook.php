<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebhookReceipt;
use App\Services\Lending\KeapWebhookProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessKeapWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $receiptId,
        public readonly array $payload,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(KeapWebhookProcessor $processor): void
    {
        $receipt = WebhookReceipt::query()->find($this->receiptId);

        if ($receipt === null) {
            return;
        }

        $processor->process($receipt, $this->payload);
    }
}

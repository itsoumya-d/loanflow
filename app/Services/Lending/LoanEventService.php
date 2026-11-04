<?php

declare(strict_types=1);

namespace App\Services\Lending;

use App\Models\LoanApplication;
use App\Models\LoanEvent;
use App\Support\HashChain;
use Illuminate\Support\Facades\DB;

class LoanEventService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(LoanApplication $application, string $type, array $payload = []): LoanEvent
    {
        return DB::transaction(function () use ($application, $type, $payload): LoanEvent {
            $locked = LoanApplication::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
            $previous = $locked->events()->orderByDesc('sequence')->value('hash');
            $sequence = ((int) $locked->events()->max('sequence')) + 1;
            $occurredAt = now()->startOfSecond();

            $hash = HashChain::next($previous, [
                'loan_application_id' => $locked->getKey(),
                'type' => $type,
                'payload' => $payload,
                'occurred_at' => $occurredAt->toIso8601String(),
            ]);

            $event = $locked->events()->create([
                'sequence' => $sequence,
                'type' => $type,
                'payload' => $payload,
                'previous_hash' => $previous,
                'hash' => $hash,
                'occurred_at' => $occurredAt,
            ]);

            return $event->refresh();
        });
    }

    public function verifyChain(LoanApplication $application): bool
    {
        $previous = null;

        foreach ($application->events()->orderBy('sequence')->get() as $event) {
            $expected = HashChain::next($previous, [
                'loan_application_id' => $event->loan_application_id,
                'type' => $event->type,
                'payload' => $event->payload,
                'occurred_at' => $event->occurred_at->toIso8601String(),
            ]);

            if (! hash_equals($expected, $event->hash)) {
                return false;
            }

            $previous = $event->hash;
        }

        return true;
    }
}

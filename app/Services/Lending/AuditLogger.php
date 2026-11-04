<?php

declare(strict_types=1);

namespace App\Services\Lending;

use App\Models\AuditLog;
use App\Support\HashChain;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $action,
        ?Model $subject = null,
        array $metadata = [],
        ?Authenticatable $actor = null,
    ): AuditLog {
        return DB::transaction(function () use ($action, $subject, $metadata, $actor): AuditLog {
            $previous = AuditLog::query()->orderByDesc('sequence')->value('hash');
            $sequence = ((int) AuditLog::query()->max('sequence')) + 1;
            $createdAt = now();
            $actorId = $actor?->getAuthIdentifier();

            $hash = HashChain::next($previous, [
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'actor_type' => $actor !== null ? $actor::class : null,
                'actor_id' => $actorId,
                'metadata' => $metadata,
                'created_at' => $createdAt->toIso8601String(),
            ]);

            return AuditLog::query()->create([
                'sequence' => $sequence,
                'actor_type' => $actor !== null ? $actor::class : null,
                'actor_id' => $actorId !== null ? (string) $actorId : null,
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'metadata' => $metadata,
                'ip_address' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 1000),
                'previous_hash' => $previous,
                'hash' => $hash,
            ])->refresh();
        });
    }

    public function verifyChain(): bool
    {
        $previous = null;

        foreach (AuditLog::query()->orderBy('sequence')->cursor() as $log) {
            $expected = HashChain::next($previous, [
                'action' => $log->action,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'actor_type' => $log->actor_type,
                'actor_id' => $log->actor_id,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at->toIso8601String(),
            ]);

            if (! hash_equals($expected, $log->hash)) {
                return false;
            }

            $previous = $log->hash;
        }

        return true;
    }
}

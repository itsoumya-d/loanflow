<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $loan_application_id
 * @property int $sequence
 * @property string $type
 * @property array<string, mixed> $payload
 * @property Carbon $occurred_at
 * @property Carbon|null $bigquery_exported_at
 * @property Carbon|null $gtm_forwarded_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class LoanEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'loan_application_id',
        'sequence',
        'type',
        'payload',
        'previous_hash',
        'hash',
        'occurred_at',
        'bigquery_exported_at',
        'gtm_forwarded_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'bigquery_exported_at' => 'datetime',
            'gtm_forwarded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LoanApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function markBigQueryExported(): void
    {
        $this->forceFill(['bigquery_exported_at' => now()])->save();
    }

    public function markGtmForwarded(): void
    {
        $this->forceFill(['gtm_forwarded_at' => now()])->save();
    }
}

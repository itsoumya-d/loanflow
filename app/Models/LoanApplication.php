<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LoanApplicationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $reference
 * @property string $applicant_name
 * @property string $email
 * @property string $phone
 * @property int $loan_amount_cents
 * @property int $annual_income_cents
 * @property string $status
 * @property string $source
 * @property string|null $keap_contact_id
 * @property Carbon|null $submitted_at
 * @property Carbon|null $decision_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class LoanApplication extends Model
{
    /** @use HasFactory<LoanApplicationFactory> */
    use HasFactory, HasUuids;

    public const STATUS_RECEIVED = 'received';

    public const STATUS_SCREENING = 'screening';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_REFERRED = 'referred';

    protected $fillable = [
        'reference',
        'applicant_name',
        'email',
        'email_hash',
        'phone',
        'loan_amount_cents',
        'loan_purpose',
        'employment_status',
        'annual_income_cents',
        'credit_check_consent',
        'privacy_consent',
        'status',
        'source',
        'idempotency_key',
        'payload_hash',
        'keap_contact_id',
        'metadata',
        'submitted_at',
        'decision_at',
    ];

    protected $hidden = [
        'phone',
        'email_hash',
        'idempotency_key',
        'payload_hash',
    ];

    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
            'loan_amount_cents' => 'integer',
            'annual_income_cents' => 'integer',
            'credit_check_consent' => 'boolean',
            'privacy_consent' => 'boolean',
            'metadata' => 'array',
            'submitted_at' => 'datetime',
            'decision_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /** @return HasMany<LoanEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(LoanEvent::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_DECLINED], true);
    }

    public function maskedEmail(): string
    {
        [$local, $domain] = explode('@', $this->email, 2);

        return mb_substr($local, 0, 2).'***@'.$domain;
    }
}

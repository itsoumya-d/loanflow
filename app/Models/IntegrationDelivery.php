<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IntegrationDelivery extends Model
{
    use HasUuids;

    protected $fillable = [
        'provider',
        'loan_application_id',
        'idempotency_key',
        'attempt',
        'status',
        'response_code',
        'response_body',
        'next_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'response_code' => 'integer',
            'next_retry_at' => 'datetime',
        ];
    }
}

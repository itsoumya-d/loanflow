<?php

declare(strict_types=1);

namespace App\Services\Lending;

use App\Models\LoanApplication;
use Illuminate\Support\Str;

class ApplicationReferenceGenerator
{
    public function generate(): string
    {
        $prefix = (string) config('loanflow.reference_prefix', 'LF');

        do {
            $reference = sprintf('%s-%s-%s', $prefix, now()->format('Y'), Str::upper(Str::random(8)));
        } while (LoanApplication::query()->where('reference', $reference)->exists());

        return $reference;
    }
}

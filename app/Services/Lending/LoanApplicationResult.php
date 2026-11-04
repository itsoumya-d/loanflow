<?php

declare(strict_types=1);

namespace App\Services\Lending;

use App\Models\LoanApplication;

final readonly class LoanApplicationResult
{
    public function __construct(
        public LoanApplication $application,
        public bool $created,
    ) {}
}

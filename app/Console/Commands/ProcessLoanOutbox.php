<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessLoanApplication;
use App\Models\LoanApplication;
use Illuminate\Console\Command;

class ProcessLoanOutbox extends Command
{
    protected $signature = 'loanflow:outbox {--limit=100 : Maximum applications to process}';

    protected $description = 'Re-dispatch pending loan applications so unexported events reach Keap, BigQuery and GTM.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $applications = LoanApplication::query()
            ->where(function ($query): void {
                $query->whereHas('events', fn ($events) => $events->whereNull('bigquery_exported_at'))
                    ->orWhereHas('events', fn ($events) => $events->whereNull('gtm_forwarded_at'));
            })
            ->latest('updated_at')
            ->limit($limit)
            ->get();

        foreach ($applications as $application) {
            ProcessLoanApplication::dispatch($application->getKey());
        }

        $this->info("Queued {$applications->count()} application(s) for outbox processing.");

        return self::SUCCESS;
    }
}

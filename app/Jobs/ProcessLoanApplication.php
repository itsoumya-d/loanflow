<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\IntegrationException;
use App\Integrations\BigQuery\BigQueryExporter;
use App\Integrations\Gtm\GtmEventForwarder;
use App\Integrations\Keap\KeapClient;
use App\Models\LoanApplication;
use App\Services\Lending\AuditLogger;
use App\Services\Lending\LoanEventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLoanApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $applicationId) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(
        KeapClient $keap,
        BigQueryExporter $bigquery,
        GtmEventForwarder $gtm,
        LoanEventService $events,
        AuditLogger $audit,
    ): void {
        $application = LoanApplication::query()->find($this->applicationId);

        if ($application === null) {
            return;
        }

        if (config('loanflow.keap.base_url') && config('loanflow.keap.client_id')) {
            try {
                $contactId = $keap->upsertContact($application);
                $application->forceFill(['keap_contact_id' => $contactId])->save();
                $events->record($application, 'keap.contact_synced', ['contact_id' => $contactId]);
            } catch (IntegrationException $exception) {
                $this->recordFailure($audit, $application, $exception);
            }
        }

        if (config('loanflow.bigquery.project_id')) {
            foreach ($application->events()->whereNull('bigquery_exported_at')->get() as $event) {
                try {
                    $bigquery->export($event);
                } catch (IntegrationException $exception) {
                    $this->recordFailure($audit, $application, $exception);
                }
            }
        }

        if (config('loanflow.gtm.server_url')) {
            foreach ($application->events()->whereNull('gtm_forwarded_at')->get() as $event) {
                try {
                    $gtm->forward($event);
                } catch (IntegrationException $exception) {
                    $this->recordFailure($audit, $application, $exception);
                }
            }
        }
    }

    private function recordFailure(AuditLogger $audit, LoanApplication $application, IntegrationException $exception): void
    {
        Log::warning('Integration delivery failed.', [
            'provider' => $exception->provider,
            'application' => $application->reference,
            'status' => $exception->statusCode,
            'message' => $exception->getMessage(),
        ]);

        $audit->record('integration.delivery_failed', $application, [
            'provider' => $exception->provider,
            'status' => $exception->statusCode,
            'message' => $exception->getMessage(),
        ]);
    }
}

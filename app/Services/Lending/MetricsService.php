<?php

declare(strict_types=1);

namespace App\Services\Lending;

use App\Models\LoanApplication;
use App\Models\LoanEvent;
use App\Models\WebhookReceipt;

class MetricsService
{
    public function render(): string
    {
        $lines = [];

        $lines[] = '# HELP loanflow_applications_total Loan applications by status.';
        $lines[] = '# TYPE loanflow_applications_total gauge';
        foreach (LoanApplication::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status') as $status => $total) {
            $lines[] = sprintf('loanflow_applications_total{status="%s"} %d', $status, $total);
        }

        $lines[] = '# HELP loanflow_events_total Domain events by type.';
        $lines[] = '# TYPE loanflow_events_total counter';
        foreach (LoanEvent::query()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type') as $type => $total) {
            $lines[] = sprintf('loanflow_events_total{type="%s"} %d', $type, $total);
        }

        $lines[] = '# HELP loanflow_outbox_pending Events not yet delivered to an external system.';
        $lines[] = '# TYPE loanflow_outbox_pending gauge';
        $lines[] = 'loanflow_outbox_pending{destination="bigquery"} '.LoanEvent::query()->whereNull('bigquery_exported_at')->count();
        $lines[] = 'loanflow_outbox_pending{destination="gtm"} '.LoanEvent::query()->whereNull('gtm_forwarded_at')->count();

        $lines[] = '# HELP loanflow_webhook_receipts_total Webhook receipts by signature validity.';
        $lines[] = '# TYPE loanflow_webhook_receipts_total counter';
        foreach (WebhookReceipt::query()->selectRaw('signature_valid, count(*) as total')->groupBy('signature_valid')->pluck('total', 'signature_valid') as $valid => $total) {
            $lines[] = sprintf('loanflow_webhook_receipts_total{valid="%s"} %d', (int) $valid === 1 ? 'true' : 'false', $total);
        }

        return implode("\n", $lines)."\n";
    }
}

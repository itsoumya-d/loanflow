<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\GtmEventController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\KeapWebhookController;
use App\Http\Controllers\Api\V1\LoanApplicationController;
use App\Http\Controllers\Api\V1\MetricsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('health', HealthController::class)->name('api.health');
    Route::get('metrics', MetricsController::class)->name('api.metrics');

    Route::middleware('auth:api')->group(function (): void {
        Route::get('loan-applications/{loanApplication}', [LoanApplicationController::class, 'show'])
            ->middleware('scope:applications:read')
            ->name('api.loan-applications.show');

        Route::get('loan-applications/{loanApplication}/timeline', [LoanApplicationController::class, 'timeline'])
            ->middleware('scope:applications:read')
            ->name('api.loan-applications.timeline');

        Route::patch('loan-applications/{loanApplication}/status', [LoanApplicationController::class, 'updateStatus'])
            ->middleware('scope:applications:manage')
            ->name('api.loan-applications.status');
    });

    Route::post('loan-applications', [LoanApplicationController::class, 'store'])
        ->middleware(['client:applications:write', 'idempotency', 'throttle:loanflow-write'])
        ->name('api.loan-applications.store');

    Route::get('machine/loan-applications/{loanApplication}', [LoanApplicationController::class, 'show'])
        ->middleware('client:applications:read')
        ->name('api.machine.loan-applications.show');

    Route::get('machine/loan-applications/{loanApplication}/timeline', [LoanApplicationController::class, 'timeline'])
        ->middleware('client:applications:read')
        ->name('api.machine.loan-applications.timeline');

    Route::post('webhooks/keap', KeapWebhookController::class)
        ->middleware('throttle:120,1')
        ->name('api.webhooks.keap');

    Route::post('events/gtm', GtmEventController::class)
        ->middleware(['hmac:gtm', 'throttle:120,1'])
        ->name('api.events.gtm');
});

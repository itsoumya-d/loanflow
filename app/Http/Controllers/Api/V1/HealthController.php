<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => false,
            'cache' => false,
        ];

        try {
            DB::select('select 1');
            $checks['database'] = true;
        } catch (Throwable) {
            //
        }

        try {
            Cache::put('loanflow:health', 'ok', 5);
            $checks['cache'] = Cache::get('loanflow:health') === 'ok';
        } catch (Throwable) {
            //
        }

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'time' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\Lending\MetricsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MetricsController
{
    public function __invoke(Request $request, MetricsService $metrics): Response
    {
        $expected = (string) config('loanflow.metrics.token', '');

        if ($expected === '') {
            abort(503, 'Metrics token is not configured.');
        }

        $provided = (string) $request->bearerToken();

        if (! hash_equals($expected, $provided)) {
            abort(401, 'Invalid metrics token.');
        }

        return response($metrics->render(), 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }
}

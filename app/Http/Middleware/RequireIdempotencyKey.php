<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireIdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) config('loanflow.idempotency.header', 'Idempotency-Key');
        $key = $request->header($header);

        if (! is_string($key) || mb_strlen($key) < 8 || mb_strlen($key) > 128) {
            return response()->json([
                'message' => "A valid {$header} header (8-128 characters) is required.",
            ], 400);
        }

        $request->attributes->set('idempotency_key', $key);

        return $next($request);
    }
}

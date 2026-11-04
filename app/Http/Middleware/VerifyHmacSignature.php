<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\InvalidWebhookSignatureException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyHmacSignature
{
    public function handle(Request $request, Closure $next, string $profile): Response
    {
        $secret = (string) config("loanflow.{$profile}.ingest_secret", '');
        $header = (string) config("loanflow.{$profile}.signature_header", 'X-Loanflow-Signature');
        $signature = $request->header($header);

        if (is_string($signature) && str_starts_with($signature, 'sha256=')) {
            $signature = substr($signature, 7);
        }

        if ($secret === '' || ! is_string($signature) || $signature === '') {
            throw new InvalidWebhookSignatureException($profile);
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            throw new InvalidWebhookSignatureException($profile);
        }

        return $next($request);
    }
}

<?php

use App\Exceptions\IdempotencyConflictException;
use App\Exceptions\IntegrationException;
use App\Exceptions\InvalidGtmEventException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\InvalidWebhookSignatureException;
use App\Http\Middleware\RequestId;
use App\Http\Middleware\RequireIdempotencyKey;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerifyHmacSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Http\Middleware\CheckTokenForAnyScope;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'hmac' => VerifyHmacSignature::class,
            'idempotency' => RequireIdempotencyKey::class,
            'security.headers' => SecurityHeaders::class,
            'request.id' => RequestId::class,
            'scope' => CheckToken::class,
            'scopes' => CheckTokenForAnyScope::class,
            'client' => EnsureClientIsResourceOwner::class,
        ]);

        $middleware->api(prepend: [
            RequestId::class,
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(fn (InvalidWebhookSignatureException $exception) => response()->json([
            'message' => $exception->getMessage(),
        ], 401));

        $exceptions->render(fn (IdempotencyConflictException $exception) => response()->json([
            'message' => $exception->getMessage(),
        ], 409));

        $exceptions->render(fn (InvalidStatusTransitionException $exception) => response()->json([
            'message' => $exception->getMessage(),
        ], 422));

        $exceptions->render(fn (InvalidGtmEventException $exception) => response()->json([
            'message' => $exception->getMessage(),
        ], 422));

        $exceptions->render(fn (IntegrationException $exception) => response()->json([
            'message' => 'An upstream integration failed.',
            'provider' => $exception->provider,
        ], 502));
    })->create();

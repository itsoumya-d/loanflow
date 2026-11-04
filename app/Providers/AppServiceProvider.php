<?php

declare(strict_types=1);

namespace App\Providers;

use App\Integrations\BigQuery\AccessTokenProvider;
use App\Integrations\BigQuery\BigQueryExporter;
use App\Integrations\BigQuery\ServiceAccountAccessTokenProvider;
use App\Integrations\Gtm\GtmEventForwarder;
use App\Integrations\Gtm\GtmEventValidator;
use App\Integrations\Keap\KeapClient;
use App\Integrations\Keap\KeapTokenProvider;
use App\Integrations\Keap\KeapWebhookVerifier;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(KeapTokenProvider::class, fn ($app): KeapTokenProvider => new KeapTokenProvider(
            $app->make(Factory::class),
            (array) config('loanflow.keap'),
        ));

        $this->app->singleton(KeapClient::class, fn ($app): KeapClient => new KeapClient(
            $app->make(Factory::class),
            $app->make(KeapTokenProvider::class),
            (array) config('loanflow.keap'),
        ));

        $this->app->singleton(KeapWebhookVerifier::class, fn (): KeapWebhookVerifier => new KeapWebhookVerifier(
            (array) config('loanflow.keap'),
        ));

        $this->app->singleton(AccessTokenProvider::class, fn ($app): ServiceAccountAccessTokenProvider => new ServiceAccountAccessTokenProvider(
            $app->make(Factory::class),
            (array) config('loanflow.bigquery'),
        ));

        $this->app->singleton(BigQueryExporter::class, fn ($app): BigQueryExporter => new BigQueryExporter(
            $app->make(Factory::class),
            $app->make(AccessTokenProvider::class),
            (array) config('loanflow.bigquery'),
        ));

        $this->app->singleton(GtmEventForwarder::class, fn ($app): GtmEventForwarder => new GtmEventForwarder(
            $app->make(Factory::class),
            (array) config('loanflow.gtm'),
        ));

        $this->app->singleton(GtmEventValidator::class, fn (): GtmEventValidator => new GtmEventValidator);
    }

    public function boot(): void
    {
        Passport::tokensCan([
            'applications:read' => 'Read loan applications',
            'applications:write' => 'Submit loan applications',
            'applications:manage' => 'Move loan applications through the decision workflow',
        ]);
        Passport::setDefaultScope(['applications:read']);
        Passport::tokensExpireIn(now()->addHours(12));
        Passport::refreshTokensExpireIn(now()->addDays(30));

        Gate::define('manage-loan-applications', fn (User $user): bool => $user->canManageApplications());

        RateLimiter::for('loanflow-write', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(30)->by((string) $key);
        });

        Model::preventLazyLoading(! $this->app->isProduction() && ! $this->app->runningUnitTests());
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
}

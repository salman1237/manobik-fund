<?php

namespace App\Providers;

use App\Contracts\SmsGateway;
use App\Services\Sms\LogSmsGateway;
use App\Support\Settings;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);

        $this->app->singleton(StripeClient::class, fn () => new StripeClient(
            config('services.stripe.secret') ?: 'sk_test_placeholder'
        ));

        // Swap for a real provider binding once SMS credentials exist
        // (spec §6 Phase 11: SMS is an optional future integration).
        $this->app->singleton(SmsGateway::class, LogSmsGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

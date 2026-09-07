<?php

namespace App\Providers;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

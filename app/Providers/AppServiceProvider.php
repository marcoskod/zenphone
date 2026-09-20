<?php

namespace App\Providers;

use App\Services\FedaPay\FedaPayService;
use App\Services\PricingService;
use App\Services\SmsProvider\Contracts\SmsProviderInterface;
use App\Services\SmsProvider\SmsPoolService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsProviderInterface::class, function ($app) {
            return new SmsPoolService($app['config']['smspool']);
        });

        $this->app->singleton(PricingService::class, function ($app) {
            return new PricingService($app['config']['smspool']);
        });

        $this->app->singleton(FedaPayService::class, function ($app) {
            return new FedaPayService($app['config']['fedapay']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Inline register-or-login is public and password-guessable: keep brute force slow.
        RateLimiter::for('quick-auth', fn (Request $request) => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perMinute(5)->by($request->ip().'|'.strtolower((string) $request->input('email'))),
        ]);

        // Every purchase step calls SMSPool/FedaPay, so cap per customer.
        RateLimiter::for('purchase', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}

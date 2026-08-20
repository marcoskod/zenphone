<?php

namespace App\Providers;

use App\Services\FiveSim\Contracts\FiveSimServiceInterface;
use App\Services\FiveSim\FiveSimService;
use App\Services\PricingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FiveSimServiceInterface::class, function ($app) {
            return new FiveSimService($app['config']['fivesim']);
        });

        $this->app->singleton(PricingService::class, function ($app) {
            return new PricingService($app['config']['fivesim']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

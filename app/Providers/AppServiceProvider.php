<?php

namespace App\Providers;

use App\Services\OrderService;
use App\Services\DiscountService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register DiscountService as a singleton
        $this->app->singleton(DiscountService::class, function ($app) {
            return new DiscountService();
        });

        // Register OrderService with DiscountService automatically resolved
        $this->app->singleton(OrderService::class, function ($app) {
            return new OrderService($app->make(DiscountService::class));
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

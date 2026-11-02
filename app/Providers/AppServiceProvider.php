<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Services\QueryMonitorService;
use App\Models\Car;
use App\Models\Booking;
use App\Observers\CarObserver;
use App\Observers\BookingObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // TLS terminates at the platform proxy; generate https URLs and secure cookies.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Register query monitoring in non-production environments
        if (config('app.debug')) {
            app(QueryMonitorService::class)->register();
        }

        // Register model observers for cache invalidation
        Car::observe(CarObserver::class);
        Booking::observe(BookingObserver::class);
    }
}



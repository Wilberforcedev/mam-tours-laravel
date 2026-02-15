<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\QueryMonitorService;

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
        // Register query monitoring in non-production environments
        if (config('app.debug')) {
            app(QueryMonitorService::class)->register();
        }
    }
}


<?php

namespace App\Providers;

use App\Services\LocationService;
use App\Services\TextBeeService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services
        $this->app->scoped(LocationService::class);

        $this->app->singleton(TextBeeService::class, function ($app) {
            return new TextBeeService(
                config('services.textbee.api_key'),
                config('services.textbee.device_id')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        // if ($this->app->environment('production')) {
        //     URL::forceScheme('https');
        // }
        // Force HTTPS if the environment is NOT local
        if (! app()->environment('local')) {
            URL::forceScheme('https');
        }
    }
}

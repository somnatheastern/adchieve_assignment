<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GeoLocationApiInterface;
use App\Services\PositionstackApiService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GeoLocationApiInterface::class, PositionstackApiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

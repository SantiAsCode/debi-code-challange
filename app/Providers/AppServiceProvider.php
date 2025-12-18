<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\FontsInUseService::class, function ($app) {
            return new \App\Services\FontsInUseService(
                config('services.fontsinuse.username', env('FONTSINUSE_USERNAME', '')),
                config('services.fontsinuse.password', env('FONTSINUSE_PASSWORD', ''))
            );
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

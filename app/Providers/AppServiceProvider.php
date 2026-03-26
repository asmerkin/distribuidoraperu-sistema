<?php

namespace App\Providers;

use App\Auth\ScannerTokenGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::extend('scanner-token', function ($app, $name, array $config) {
            return new ScannerTokenGuard($app['request']);
        });
    }
}

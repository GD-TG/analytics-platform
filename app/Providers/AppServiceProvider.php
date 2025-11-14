<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Direct\DirectClient;
use App\Services\Metrika\MetrikaClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Регистрируем DirectClient
        $this->app->singleton(DirectClient::class, function ($app) {
            return new DirectClient();
        });

        // Регистрируем MetrikaClient
        $this->app->singleton(MetrikaClient::class, function ($app) {
            return new MetrikaClient();
        });
    }

    public function boot(): void
    {
        //
    }
}
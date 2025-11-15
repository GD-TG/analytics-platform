<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AI\HuggingFaceService;

class AIServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(HuggingFaceService::class, function ($app) {
            return new HuggingFaceService();
        });
    }

    public function boot()
    {
        // Boot logic if needed
    }
}

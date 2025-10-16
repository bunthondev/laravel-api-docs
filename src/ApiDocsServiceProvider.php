<?php

namespace Puchan\LaravelApiDocs;

use Illuminate\Support\ServiceProvider;
use Puchan\LaravelApiDocs\Http\Controllers\ApiDocController;
use Puchan\LaravelApiDocs\Services\ApiDocGenerator;

class ApiDocsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/api-docs.php' => config_path('api-docs.php'),
        ], 'api-docs-config');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/api-docs'),
        ], 'api-docs-views');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'api-docs');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/api-docs.php', 'api-docs'
        );

        // Register services
        $this->app->singleton(ApiDocGenerator::class, function ($app) {
            return new ApiDocGenerator();
        });

        // Register controller
        $this->app->make(ApiDocController::class);
    }
}

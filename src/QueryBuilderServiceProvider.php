<?php

namespace Laravilt\QueryBuilder;

use Illuminate\Support\ServiceProvider;

class QueryBuilderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravilt-query-builder.php',
            'laravilt-query-builder'
        );

        // Register any services, bindings, or singletons here
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'query-builder');


        // Load web routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');


        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__ . '/../config/laravilt-query-builder.php' => config_path('laravilt-query-builder.php'),
            ], 'laravilt-query-builder-config');

            // Publish assets
            $this->publishes([
                __DIR__ . '/../dist' => public_path('vendor/laravilt/query-builder'),
            ], 'laravilt-query-builder-assets');


            // Register commands
            $this->commands([
                Commands\InstallQueryBuilderCommand::class,
            ]);
        }
    }
}

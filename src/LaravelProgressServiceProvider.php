<?php

namespace Alboradait\LaravelProgress;

use Illuminate\Support\ServiceProvider;

class LaravelProgressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Aquí puedes registrar bindings o configuraciones.
    }

    public function boot(): void
    {
        // Aquí cargas rutas, vistas, migraciones, publicaciones, etc.

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-progress');

        $this->publishes([
            __DIR__.'/../config/progress.php' => config_path('progress.php'),
        ], 'progress-config');
    }
}

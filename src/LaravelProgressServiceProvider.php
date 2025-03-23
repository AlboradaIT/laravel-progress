<?php

namespace AlboradaIT\LaravelProgress;

use AlboradaIT\LaravelProgress\Contracts\ShouldTriggerProgressRecalculation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class LaravelProgressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/progress.php', 'progress');
    }

    public function boot(): void
    {
        // Aquí cargas rutas, vistas, migraciones, publicaciones, etc.

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-progress');

        $this->publishes([
            __DIR__.'/../config/progress.php' => config_path('progress.php'),
        ], 'progress-config');

        Event::listen(
            ShouldTriggerProgressRecalculation::class,
            \AlboradaIT\LaravelProgress\Listeners\RecalculateProgressListener::class
        );
    }
}

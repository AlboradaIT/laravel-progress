<?php

namespace Alboradait\LaravelProgress\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            \Alboradait\LaravelProgress\LaravelProgressServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Aquí puedes configurar el entorno si tu paquete depende de algo
    }
}

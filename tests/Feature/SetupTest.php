<?php

namespace AlboradaIT\LaravelProgress\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use AlboradaIT\LaravelProgress\Tests\TestCase;

class SetupTest extends TestCase
{
    public function test_it_runs_migrations()
    {
        // Ejecutar las migraciones registradas por el paquete
        $this->artisan('migrate')->run();

        // Verificamos que la tabla exista
        $this->assertTrue(Schema::hasTable('progress_records'));
    }
}
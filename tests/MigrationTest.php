<?php

namespace Alboradait\LaravelProgress\Tests;

use Illuminate\Support\Facades\Schema;

class MigrationTest extends TestCase
{
    public function test_it_runs_migrations()
    {
        $this->artisan('migrate')->run();
        $this->assertTrue(Schema::hasTable('progress_records'));
    }
}
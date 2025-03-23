<?php

namespace AlboradaIT\LaravelProgress\Tests;

use AlboradaIT\LaravelProgress\Contracts\Progressable;
use AlboradaIT\LaravelProgress\Support\Traits\TracksProgress;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            \AlboradaIT\LaravelProgress\LaravelProgressServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        //Event::fake();
        
        $this->artisan('migrate')->run();
        $this->createUsersTable();
        $this->createCoursesTable();
    }

    protected function createUsersTable()
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    protected function createCoursesTable()
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('courses', function ($table) {
            $table->id();
            $table->timestamps();
        });
    }

    protected function makeCourse(array $definedSteps = [], ?Closure $getWeightForStep = null): Progressable & Model
    {
        $course = new class($definedSteps, $getWeightForStep) extends Model implements Progressable {
            use TracksProgress {
                TracksProgress::getWeightForStep as traitGetWeightForStep;
            }

            public array $completedStepsByUser = [];

            protected $table = 'courses';

            protected array $customDefinedSteps = [];
            protected ?Closure $customWeightFunction;

            public function __construct(array $definedSteps = [], ?Closure $getWeightForStep = null)
            {
                parent::__construct();
                $this->customDefinedSteps = $definedSteps;
                $this->customWeightFunction = $getWeightForStep;
            }

            public function definedSteps(): array
            {
                return $this->customDefinedSteps;
            }

            public function deleteStep(string $step): void
            {
                $this->customDefinedSteps = array_diff($this->customDefinedSteps, [$step]);
            }

            public function getCompletedSteps(User $user): array
            {
                return $this->completedStepsByUser[$user->id] ?? [];
            }

            public function getWeightForStep(mixed $step): int
            {
                if ($this->customWeightFunction) {
                    return ($this->customWeightFunction)($step);
                }
        
                return $this->traitGetWeightForStep($step);
            }
        };

        return $course;
    }

}

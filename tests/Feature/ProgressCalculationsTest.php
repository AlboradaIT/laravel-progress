<?php

namespace AlboradaIT\LaravelProgress\Tests\Feature;

use AlboradaIT\LaravelProgress\Models\ProgressRecord;
use AlboradaIT\LaravelProgress\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Event;

class ProgressCalculationsTest extends TestCase
{
    public function test_progress_percentage_is_calculated_correctly()
    {
        $course = $this->makeCourse(definedSteps: ['step1', 'step2', 'step3']);
        $course->save();

        $user = User::create([]);

        // 3 pasos definidos en el helper
        // Solo 1 completado
        $course->completedStepsByUser[$user->id] = ['step1'];
        $progress = $course->updateUserProgress($user);

        $this->assertEquals(33.33, round($progress->percentage, 2));
    }

    public function test_it_calculates_weighted_progress_correctly()
    {
        $chapters = [
            'intro' => ['duration' => 500],
            'chapter_1' => ['duration' => 500],
            'chapter_2' => ['duration' => 1000],
        ];

        $course = $this->makeCourse(definedSteps: $chapters, getWeightForStep: function ($step) {
            return $step['duration'];
        });
        $course->save();

        $user = User::create([]);
        $course->completedStepsByUser[$user->id] = [
            $chapters['intro'],
        ];

        $progress = $course->updateUserProgress($user);
        $expected = 25.0;
        $this->assertEquals(round($expected, 2), round($progress->percentage, 2));
    }

    public function test_it_recalculates_progress_when_step_is_removed_from_progressable()
    {
        $course = $this->makeCourse(definedSteps: ['step1', 'step2', 'step3']);
        $course->save();

        $user1 = User::create([]);
        $user2 = User::create([]);

        // Progreso parcial
        $course->completedStepsByUser[$user1->id] = ['step1', 'step2'];
        $course->completedStepsByUser[$user2->id] = ['step1'];
       
        $course->updateUserProgress($user1);
        $course->updateUserProgress($user2);

        // Eliminamos un paso
        $course->deleteStep('step3');

        // Actualizamos los progresos (de esto se encargaría un listener normalmente)
        $course->updateProgresses();

        // Comprobamos que los progresos se han recalculado
        $progress1 = $course->progresses()->where('user_id', $user1->id)->first();
        $progress2 = $course->progresses()->where('user_id', $user2->id)->first();

        $this->assertEquals(100, $progress1->percentage);
        $this->assertEquals(50, $progress2->percentage);
    }

    public function test_reset_progress_resets_percentage_and_status()
    {
        $course = $this->makeCourse();
        $course->save();
        $user = User::create([]);
        $course->completedStepsByUser[$user->id] = ['step1'];
        $course->updateUserProgress($user);

        $course->resetProgress($user);

        $record = $course->progressForUser($user);

        $this->assertEquals(0, $record->percentage);
        $this->assertEquals(ProgressRecord::STATUS_IN_PROGRESS, $record->status);
    }
}

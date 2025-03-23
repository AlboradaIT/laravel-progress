<?php

namespace AlboradaIT\LaravelProgress\Tests\Feature;

use AlboradaIT\LaravelProgress\Contracts\ShouldTriggerProgressRecalculation;
use AlboradaIT\LaravelProgress\Events\ProgressAbandoned;
use AlboradaIT\LaravelProgress\Events\ProgressCompleted;
use AlboradaIT\LaravelProgress\Events\ProgressRestarted;
use AlboradaIT\LaravelProgress\Events\ProgressStarted;
use AlboradaIT\LaravelProgress\Events\ProgressUpdated;
use AlboradaIT\LaravelProgress\Listeners\RecalculateProgressListener;
use AlboradaIT\LaravelProgress\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Event;

class ProgressEventsTest extends TestCase
{
    public function test_it_listens_to_should_trigger_progress_recalculation_event()
    {
        Event::fake();
        Event::assertListening(ShouldTriggerProgressRecalculation::class, RecalculateProgressListener::class);
    }

    public function test_id_dispatches_progress_updated_event_for_each_user_when_updating_progresses()
    {
        $course = $this->makeCourse(definedSteps: ['step1', 'step2', 'step3']);
        $course->save();

        $user1 = User::create([]);
        $user2 = User::create([]);

        $course->completedStepsByUser[$user1->id] = ['step1'];
        $course->completedStepsByUser[$user2->id] = ['step1'];

        $course->updateUserProgress($user1);
        $course->updateUserProgress($user2);

        // Reseteamos la fachada de eventos
        Event::fake();

        // Simulamos que ambos usuarios avanzaron
        $course->completedStepsByUser[$user1->id] = ['step1', 'step2'];
        $course->completedStepsByUser[$user2->id] = ['step1', 'step2'];

        $course->updateProgresses();
        Event::assertDispatchedTimes(ProgressUpdated::class, 2);
    }

    public function test_it_does_not_dispatch_progress_updated_event_if_progress_does_not_change()
    {
        $course = $this->makeCourse(); // mismos steps
        $course->save();

        $user = User::create();

        // Primera llamada: crea el progreso y lanza evento (como los progresos no existen, se considera que cambió)
        $progress = $course->updateUserProgress($user);

        // Reseteamos la fachada de eventos
        Event::fake();

        // Segunda llamada: no cambia el progreso, no lanza evento
        $progress = $course->updateUserProgress($user);
        Event::assertNotDispatched(ProgressUpdated::class);
    }

    public function test_it_dispatches_progress_started_when_created_with_percentage()
    {
        Event::fake();

        $course = $this->makeCourse();
        $course->save();

        $user = User::create([]);
        $course->completedStepsByUser[$user->id] = ['step1']; // 1 de 3 => > 0%

        $course->updateUserProgress($user);

        Event::assertDispatched(ProgressStarted::class);
    }

    public function test_it_dispatches_progress_updated_event_when_progress_changes()
    {
        $course = $this->makeCourse(definedSteps: ['step1', 'step2', 'step3']);
        $course->save();

        $user = User::create([]);

        // Progreso parcial
        $course->completedStepsByUser[$user->id] = ['step1'];
        $progress = $course->updateUserProgress($user);

        // Reseteamos la fachada de eventos
        Event::fake();

        // Completamos el progreso
        $course->completedStepsByUser[$user->id] = ['step1', 'step2'];
        $progress = $course->updateUserProgress($user);

        Event::assertDispatched(ProgressUpdated::class);
    }

    public function test_it_does_not_dispatch_progress_updated_event_when_progress_does_not_change()
    {
        $course = $this->makeCourse(definedSteps: ['step1', 'step2', 'step3']);
        $course->save();

        $user = User::create([]);

        // Progreso parcial
        $course->completedStepsByUser[$user->id] = ['step1', 'step2'];
        $progress = $course->updateUserProgress($user);

        // Reseteamos la fachada de eventos
        Event::fake();

        // Completamos el progreso
        $course->completedStepsByUser[$user->id] = ['step1', 'step2'];
        $progress = $course->updateUserProgress($user);

        Event::assertNotDispatched(ProgressUpdated::class);
    }

    public function test_it_does_not_dispatch_progress_udpated_event_when_progress_is_reset()
    {
        $course = $this->makeCourse(definedSteps: ['step1', 'step2', 'step3']);
        $course->save();

        $user = User::create([]);

        // Progreso parcial
        $course->completedStepsByUser[$user->id] = ['step1', 'step2'];
        $progress = $course->updateUserProgress($user);

        // Reseteamos la fachada de eventos
        Event::fake();

        // Reseteamos el progreso
        $course->completedStepsByUser[$user->id] = [];
        $course->resetProgress($user);

        Event::assertNotDispatched(ProgressUpdated::class);
    }

    public function test_it_does_not_dispatch_progress_updated_event_when_progress_is_completed()
    {
        $course = $this->makeCourse(definedSteps: ['step1', 'step2', 'step3']);
        $course->save();

        $user = User::create([]);

        // Progreso parcial
        $course->completedStepsByUser[$user->id] = ['step1', 'step2'];
        $progress = $course->updateUserProgress($user);

        // Reseteamos la fachada de eventos
        Event::fake();

        // Completamos el progreso
        $course->completedStepsByUser[$user->id] = ['step1', 'step2', 'step3'];
        $progress = $course->updateUserProgress($user);

        Event::assertNotDispatched(ProgressUpdated::class);
    }

    public function test_it_dispatches_progress_completed_when_status_changes_to_completed()
    {
        Event::fake();

        $course = $this->makeCourse(definedSteps: ['step1', 'step2', 'step3']);
        $course->save();

        $user = User::create([]);
        $course->completedStepsByUser[$user->id] = ['step1']; // inicia parcial
        $course->updateUserProgress($user);

        // Lo completamos
        $course->completedStepsByUser[$user->id] = ['step1', 'step2', 'step3'];
        $course->updateUserProgress($user);

        Event::assertDispatched(ProgressCompleted::class);
    }

    public function test_it_dispatches_progress_abandoned_when_status_changes_to_abandoned()
    {
        Event::fake();

        config(['progress.abandon_after' => 3600 * 24 * 10]); // marcará como abandonado tras 10 días

        $course = $this->makeCourse();
        $course->save();

        $user = User::create([]);
        $course->completedStepsByUser[$user->id] = ['step1'];
        $course->updateUserProgress($user);

        // Forzamos un updated_at viejo para simular abandono
        $progress = $course->progresses()->where('user_id', $user->id)->first();
        $progress->updated_at = now()->subDays(10);
        $progress->save();

        $course->updateUserProgress($user);

        Event::assertDispatched(ProgressAbandoned::class);
    }

    public function test_it_does_not_dispatch_any_event_if_nothing_changes()
    {
        Event::fake();

        $course = $this->makeCourse();
        $course->save();

        $user = User::create([]);
        $course->completedStepsByUser[$user->id] = ['step1', 'step2'];
        $course->updateUserProgress($user);

        // Mismo progreso, sin cambios
        Event::fake();
        $course->updateUserProgress($user);

        Event::assertNotDispatched(ProgressStarted::class);
        Event::assertNotDispatched(ProgressUpdated::class);
        Event::assertNotDispatched(ProgressCompleted::class);
        Event::assertNotDispatched(ProgressAbandoned::class);
    }

    public function test_it_dispatches_progress_restart_event_when_progress_is_reset()
    {
        Event::fake();

        $course = $this->makeCourse();
        $course->save();
        $user = User::create([]);
        $course->completedStepsByUser[$user->id] = ['step1'];
        $course->updateUserProgress($user);
        $course->resetProgress($user);

        Event::assertDispatched(ProgressRestarted::class);
    }
}

<?php 

namespace AlboradaIT\LaravelProgress\Listeners;

use AlboradaIT\LaravelProgress\Contracts\ShouldTriggerProgressRecalculation;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecalculateProgressListener implements ShouldQueue
{
    public function viaConnection(): string
    {
        return config('progress.queue_connection');
    }

    public function viaQueue(): string
    {
        return config('progress.queue_name');
    }

    public function handle(ShouldTriggerProgressRecalculation $event)
    {
        $progressables = $event->getProgressables();
        foreach ($progressables as $progressable) {
            $progressable->updateProgresses();
        }
    }
}
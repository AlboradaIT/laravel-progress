<?php 

namespace AlboradaIT\LaravelProgress\Events;

use AlboradaIT\LaravelProgress\Contracts\Progressable;
use AlboradaIT\LaravelProgress\Models\ProgressRecord;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProgressUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ProgressRecord $progress,
    ) {}
}
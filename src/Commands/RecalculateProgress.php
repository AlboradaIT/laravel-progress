<?php

namespace AlboradaIT\LaravelProgress\Commands;

use AlboradaIT\LaravelProgress\Models\ProgressRecord;
use Illuminate\Console\Command;

class RecalculateProgress extends Command
{
    protected $signature = 'progress:recalculate {--type=} {--id=}';
    protected $description = 'Recalculate progress records';

    public function handle()
    {
        $query = ProgressRecord::query();
        if ( $type = $this->option('type') ) {
            $query->where('progressable_type', $type);
        }
        if ( $id = $this->option('id') ) {
            $query->where('progressable_id', $id);
        }

        $query->chunk(100, function ($records) {
            foreach ($records as $record) {
                $progressable = $record->progressable;
                $progressable->updateUserProgress($record->user);
            }
        });

        $this->info('Progress recalculated successfully.');
    }
}

<?php

namespace AlboradaIT\LaravelProgress\Support\Traits;

use AlboradaIT\LaravelProgress\Events\ProgressAbandoned;
use AlboradaIT\LaravelProgress\Events\ProgressCompleted;
use AlboradaIT\LaravelProgress\Events\ProgressRestarted;
use AlboradaIT\LaravelProgress\Events\ProgressStarted;
use AlboradaIT\LaravelProgress\Events\ProgressUpdated;
use AlboradaIT\LaravelProgress\Models\ProgressRecord;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User;

trait TracksProgress
{
    public function progresses(): MorphMany
    {
        return $this->morphMany(ProgressRecord::class, 'progressable');
    }

    public function progressForUser(User $user): ?ProgressRecord
    {
        return $this->progresses()->where('user_id', $user->id)->first();
    }

    private function ensureProgressForUser(User $user): ProgressRecord
    {
        return $this->progresses()->firstOrCreate(['user_id' => $user->id]);
    }

    public function updateUserProgress(User $user): ProgressRecord
    {
        $record = $this->ensureProgressForUser($user);
        $wasRecentlyCreated = !$record->exists || $record->wasRecentlyCreated;
        $previousPercentage = $record->percentage;

        $record->percentage = $this->calculateProgress($user);
        $record->status = $this->determineStatus($user);
        $record->save();

        if ($wasRecentlyCreated && $record->status === ProgressRecord::STATUS_IN_PROGRESS) {
            ProgressStarted::dispatch($record);
            return $record;
        }

        if ( $record->wasChanged('status') ) {
            match ($record->status) {
                ProgressRecord::STATUS_COMPLETED => ProgressCompleted::dispatch($record),
                ProgressRecord::STATUS_ABANDONED => ProgressAbandoned::dispatch($record),
                default => null,
            };
            return $record;
        }

        if ( $record->wasChanged('percentage') ) {
            ProgressUpdated::dispatch($record);
        }

        return $record;
    }

    public function resetProgress(User $user): void
    {
        $record = $this->ensureProgressForUser($user);
        $record->percentage = 0;
        $record->status = ProgressRecord::STATUS_IN_PROGRESS;
        $record->meta = null;
        $record->save();

        ProgressRestarted::dispatch($record);
    }

    public function updateProgresses(): void
    {
        $this->progresses()->with('user')->lazy()->each(fn ($record) => $this->updateUserProgress($record->user));
    }

    public function scopeCompletedByUser($query, User $user)
    {
        return $query->whereHas('progresses', fn ($q) => 
            $q->where('user_id', $user->id)->where('percentage', '>=', 100)
        );
    }
    public function scopeWithAnyStatusForUser($query, User $user, array $statuses)
    {
        $query->whereHas('progresses', function ($q) use ($user, $statuses) {
            $q->where('user_id', $user->id)
                ->whereIn('progress_records.status', $statuses);
        });

        if ( in_array('pending', $statuses) ) {
            $query->orWhereDoesntHave('progresses', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }
    }
    public function scopeWithProgressForUser($query, User $user)
    {
        return $query->whereHas('progresses', fn ($q) => $q->where('user_id', $user->id));
    }

    public function scopeInProgressByUser($query, User $user)
    {
        return $query->whereHas('progresses', fn ($q) => 
            $q->where('user_id', $user->id)->where('percentage', '<', 100)
        );
    }

    public function scopeAbandonedByUser($query, User $user)
    {
        return $query->whereHas('progresses', fn ($q) => 
            $q->where('user_id', $user->id)->where('status', ProgressRecord::STATUS_ABANDONED)
        );
    }

    protected function determineStatus(User $user): string
    {
        if ( $this->determineCompleted($user) ) {
            return ProgressRecord::STATUS_COMPLETED;
        }

        if ( $this->determineAbandoned($user) ) {
            return ProgressRecord::STATUS_ABANDONED;
        }

        return ProgressRecord::STATUS_IN_PROGRESS;
    }

    protected function calculateProgress(User $user): float
    {
        $totalWeight = $this->getTotalWeight();
        $completedWeight = $this->getCompletedWeight($user);

        return $totalWeight > 0 ? ($completedWeight / $totalWeight) * 100 : 0;
    }

    protected function getWeightForStep(mixed $step): int
    {
        return 1;
    }

    protected function getTotalWeight(): int
    {
        return array_sum(array_map(fn ($step) => $this->getWeightForStep($step), $this->definedSteps()));
    }

    protected function getCompletedWeight(User $user): int
    {
        return array_sum(array_map(fn ($step) => $this->getWeightForStep($step), $this->getCompletedSteps($user)));
    }

    protected function determineCompleted(User $user): bool
    {
        return $this->calculateProgress($user) >= 100;
    }

    protected function determineAbandoned(User $user): bool
    {
        $record = $this->progressForUser($user);

        if ( ! $record ) {
            return false;
        }

        $timeout = config('progress.abandon_after');

        return $record->updated_at->lt(now()->subSeconds($timeout));
    }

    public function isCompleted(User $user): bool
    {
        return $this->progressForUser($user)?->status === ProgressRecord::STATUS_COMPLETED;
    }
    public function isInProgress(User $user): bool
    {
        return $this->progressForUser($user)?->status === ProgressRecord::STATUS_IN_PROGRESS;
    }
    public function isAbandoned(User $user): bool
    {
        return $this->progressForUser($user)?->status === ProgressRecord::STATUS_ABANDONED;
    }
}
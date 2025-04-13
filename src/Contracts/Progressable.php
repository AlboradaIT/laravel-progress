<?php

namespace AlboradaIT\LaravelProgress\Contracts;

use AlboradaIT\LaravelProgress\Models\ProgressRecord;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User;

interface Progressable
{
    public function progresses(): MorphMany;
    public function definedSteps(): array;
    public function getCompletedSteps(User $user): array;
    public function updateUserProgress(User $user): ProgressRecord;
    public function updateProgresses(): void;
    public function isCompleted(User $user): bool;
    public function isAbandoned(User $user): bool;
    public function isInProgress(User $user): bool;
}
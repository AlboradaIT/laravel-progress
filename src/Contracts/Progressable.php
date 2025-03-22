<?php

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User;

interface Progressable
{
    public function progresses(): MorphMany;

    public function definedSteps(): array;

    public function calculateProgress(User $user): float;

    public function isCompleted(User $user): bool;

    public function getCompletedSteps(User $user): array;
}
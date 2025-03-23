<?php

namespace AlboradaIT\LaravelProgress\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User;

class ProgressRecord extends Model
{
    protected $guarded = [];

    const STATUS_COMPLETED = 'completed';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_ABANDONED = 'abandoned';

    protected $casts = [
        'meta' => 'array',
        'completed_at' => 'datetime',
    ];

    public function progressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

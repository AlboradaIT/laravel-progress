# Add progress tracking to your eloquent models

This package allows you to easily track user progress through any kind of progressable resource: courses, tutorials, onboarding flows, or any custom process with defined steps.

---

## Features

- Easily track progress for Eloquent models implementing `Progressable`
- Built-in trait `TracksProgress` with sensible defaults
- Progress-related events:
  - `ProgressStarted`
  - `ProgressUpdated`
  - `ProgressCompleted`
  - `ProgressAbandoned`
  - `ProgressRestarted`
- Automatic recalculation on demand
- Weighted step support
- Blade components for displaying progress
- Configuration-driven
- Test-covered and queue-ready
- In development: Progress Reports

---

## Installation

```bash
composer require alboradait/laravel-progress
```

If you're using Laravel 11+, package auto-discovery is enabled.

To publish the config file:

```bash
php artisan vendor:publish --tag=progress-config
```

To publish views (Blade components):

```bash
php artisan vendor:publish --tag=progress-views
```

---

## Usage

### 1. Implement the `Progressable` Interface

```php
use AlboradaIT\LaravelProgress\Contracts\Progressable;
use AlboradaIT\LaravelProgress\Support\Traits\TracksProgress;

class Course extends Model implements Progressable
{
    use TracksProgress;

    // Define the steps of a progress
    public function definedSteps(): array
    {
        return $this->learningUnits;
    }

    public function getCompletedSteps(User $user): array
    {
        return $user->completedLearningUnits;
    }
}
```

### 2. Update Progress

```php
$course->updateUserProgress($user);
```

This will update the `ProgressRecord`, and dispatch events if something changed.

### 3. Reset Progress

```php
$course->resetProgress($user);
```

> 🔔 Note: Resetting the progress record only affects the internal `ProgressRecord` (percentage, status, etc). It **does not** remove any external state like `completedLearningUnits` — you're responsible for clearing that if needed.

### 4. Listen to Events

All progress events are dispatched via Laravel's event system, so you can listen to them using listeners, jobs, or observers.

---

## Events

| Event                   | Description                                     |
|------------------------|-------------------------------------------------|
| `ProgressStarted`      | Fired when a new progress starts               |
| `ProgressUpdated`      | Fired when percentage changes                  |
| `ProgressCompleted`    | Fired when 100% progress is reached            |
| `ProgressAbandoned`    | Fired when no progress after a time threshold  |
| `ProgressRestarted`    | Fired when progress is manually reset          |

---

## Configuration

After publishing the config file, you'll find `config/progress.php`:

```php
return [
    'queue_connection' => env('PROGRESS_QUEUE_CONNECTION', 'sync'),
    'queue_name' => env('PROGRESS_QUEUE', 'default'),
    'abandon_after' => 3600 * 24 * 30, // 30 days (in seconds)
];
```

---

## Advanced Features

### Weighted Steps

Define steps with metadata (e.g., duration):

```php
public function definedSteps(): array {
    return [
        ['name' => 'intro', 'duration' => 300],
        ['name' => 'chapter_1', 'duration' => 1200],
        ['name' => 'chapter_2', 'duration' => 600],
    ];
}

public function getWeightForStep(mixed $step): int {
    return $step['duration'] ?? 1;
}
```

### Recalculating Progresses

Trigger mass recalculations by dispatching events that implement the `ShouldTriggerProgressRecalculation` interface:

```php
use AlboradaIT\LaravelProgress\Contracts\ShouldTriggerProgressRecalculation;

class LearningUnitDeleted implements ShouldTriggerProgressRecalculation
{
    public function __construct(public Course $course) {}

    public function getProgressables(): array
    {
        return [$this->course];
    }
}
```

> 📝 You can return **multiple progressables** from `getProgressables()`. All of them will be recalculated.

---

## Queue Behavior

- `updateUserProgress($user)` runs **immediately**. It's designed for real-time UI updates or per-user actions.
- Events triggered by implementing `ShouldTriggerProgressRecalculation` are **queued** and processed asynchronously. This allows recalculating hundreds or thousands of progresses efficiently.

To change the queue connection or name, edit `config/progress.php`.

If you use `queue_connection = sync`, listeners will be run immediately.

---

## Blade Components

This package includes a couple of Blade components:

```blade
<x-laravel-progress::progress-bar :progress="$record" />
<x-laravel-progress::circular-progress :progress="$record" />
```

You can publish and customize the views:

```bash
php artisan vendor:publish --tag=progress-views
```

---

## Testing

This package is covered with a comprehensive test suite. To run tests:

```bash
vendor/bin/phpunit
```

> Uses Orchestra Testbench for package testing.

---

## License

MIT License. Copyright (c) Alborada IT.
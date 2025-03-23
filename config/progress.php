<?php 

return [
    'queue_name' => env('PROGRESS_QUEUE', 'default'),
    'queue_connection' => env('PROGRESS_QUEUE_CONNECTION', 'sync'),
    'abandon_after' => env('PROGRESS_ABANDON_AFTER_SECONDS', 604800), // 7 días por defecto
];
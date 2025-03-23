@props(['progress' => 0])

@php
    $radius = 45;
    $circumference = 2 * pi() * $radius;
    $offset = $circumference - ($progress / 100) * $circumference;
@endphp

<div class="relative inline-flex items-center justify-center">
    <svg class="w-24 h-24 transform -rotate-90">
        <circle stroke-width="8" stroke="currentColor" fill="transparent"
            r="{{ $radius }}" cx="50" cy="50" class="text-gray-200"/>

        <circle stroke-width="8" stroke-dasharray="{{ $circumference }}"
            stroke-dashoffset="{{ $offset }}" stroke-linecap="round"
            stroke="currentColor" fill="transparent"
            r="{{ $radius }}" cx="50" cy="50"
            class="text-blue-600 transition-stroke-dashoffset duration-300"/>
    </svg>

    <div class="absolute text-sm font-semibold text-gray-700">
        @if($slot->isNotEmpty())
            {{ $slot }}
        @else
            {{ $progress }}%
        @endif
    </div>
</div>

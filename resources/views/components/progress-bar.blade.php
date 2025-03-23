@props(['progress' => 0, 'label' => '', 'helperText' => '', 'showProgress' => true])

<div class="space-y-1">
    @if($label)
        <div class="text-sm font-medium text-gray-700">{{ $label }}</div>
    @endif
    <div class="w-full bg-gray-200 rounded-full h-2.5">
        <div class="bg-blue-600 h-2.5 rounded-full transition-width duration-300"
             style="width: {{ $progress }}%;"></div>
    </div>
    @if($helperText || $showProgress)
        <div class="flex justify-between text-xs text-gray-500">
            <span>{{ $helperText }}</span>
            @if($showProgress)
                <span>{{ $progress }}%</span>
            @endif
        </div>
    @endif
</div>

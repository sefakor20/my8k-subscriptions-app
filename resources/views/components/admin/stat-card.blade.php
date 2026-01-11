@props([
    'title',
    'value',
    'icon',
    'color' => 'blue',
    'trend' => null,
])

@php
    $colorClasses = [
        'blue' => 'text-blue-600 dark:text-blue-400',
        'green' => 'text-green-600 dark:text-green-400',
        'red' => 'text-red-600 dark:text-red-400',
        'yellow' => 'text-yellow-600 dark:text-yellow-400',
        'purple' => 'text-purple-600 dark:text-purple-400',
    ];

    $iconColorClass = $colorClasses[$color] ?? $colorClasses['blue'];
@endphp

<div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <flux:text variant="muted" class="text-sm font-medium">{{ $title }}</flux:text>
            <flux:heading size="2xl" class="mt-2 font-semibold">{{ $value }}</flux:heading>

            @if($trend)
                <div class="mt-2 flex items-center gap-1 text-sm">
                    @if($trend > 0)
                        <flux:icon.arrow-trending-up class="size-4 text-green-600 dark:text-green-400" />
                        <span class="text-green-600 dark:text-green-400">+{{ $trend }}%</span>
                    @elseif($trend < 0)
                        <flux:icon.arrow-trending-down class="size-4 text-red-600 dark:text-red-400" />
                        <span class="text-red-600 dark:text-red-400">{{ $trend }}%</span>
                    @else
                        <span class="text-zinc-600 dark:text-zinc-400">No change</span>
                    @endif
                </div>
            @endif
        </div>

        <div class="ml-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-{{ $color }}-100 dark:bg-{{ $color }}-900/20">
                <flux:icon :name="$icon" class="size-6 {{ $iconColorClass }}" />
            </div>
        </div>
    </div>
</div>

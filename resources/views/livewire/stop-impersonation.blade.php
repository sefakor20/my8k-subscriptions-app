<div class="mx-2 mb-2">
    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 p-3">
        <div class="flex items-center gap-2">
            <flux:icon.eye class="size-5 text-amber-600 dark:text-amber-400 shrink-0" />
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                    Impersonating
                </p>
                <p class="text-xs text-amber-600 dark:text-amber-400 truncate">
                    {{ auth()->user()->email }}
                </p>
            </div>
        </div>
        <flux:button
            wire:click="stop"
            variant="subtle"
            size="sm"
            class="w-full mt-2 !bg-amber-100 dark:!bg-amber-800/50 !text-amber-800 dark:!text-amber-200 hover:!bg-amber-200 dark:hover:!bg-amber-800"
            icon="arrow-left-start-on-rectangle"
        >
            Stop Impersonation
        </flux:button>
    </div>
</div>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Notification Settings') }}</flux:heading>

    <x-settings.layout :heading="__('Notifications')" :subheading="__('Manage your email notification preferences')">
        <div class="my-6 w-full space-y-6">
            {{-- Critical Notifications (Always On) --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-start justify-between">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 mt-0.5">
                            <flux:icon name="{{ $criticalCategory->icon() }}" variant="outline" class="size-5 text-red-500" />
                        </div>
                        <div>
                            <flux:heading size="sm">{{ $criticalCategory->label() }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                                {{ $criticalCategory->description() }}
                            </flux:text>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <flux:switch checked disabled />
                    </div>
                </div>
                <flux:text class="text-xs text-zinc-400 dark:text-zinc-500 mt-2 ml-8">
                    {{ __('These notifications cannot be disabled for account security.') }}
                </flux:text>
            </div>

            {{-- Configurable Notifications --}}
            @foreach ($categories as $category)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <flux:icon name="{{ $category->icon() }}" variant="outline" class="size-5 text-zinc-500 dark:text-zinc-400" />
                            </div>
                            <div>
                                <flux:heading size="sm">{{ $category->label() }}</flux:heading>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                                    {{ $category->description() }}
                                </flux:text>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <flux:switch
                                wire:click="togglePreference('{{ $category->value }}')"
                                :checked="$preferences[$category->value] ?? true"
                            />
                        </div>
                    </div>
                </div>
            @endforeach

            <x-action-message class="me-3" on="preference-updated">
                {{ __('Preferences saved.') }}
            </x-action-message>
        </div>
    </x-settings.layout>
</section>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Page Header --}}
    <div class="mb-8">
        <flux:heading size="xl" class="font-bold">Streaming Apps</flux:heading>
        <flux:text variant="muted" class="mt-2">
            Download apps to stream content on your devices using your subscription credentials
        </flux:text>
    </div>

    {{-- Recommended Apps Section --}}
    @if ($this->recommendedApps->isNotEmpty())
        <div class="mb-8">
            <flux:heading size="lg" class="mb-4">Recommended Apps</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($this->recommendedApps as $app)
                    <div wire:key="recommended-{{ $app->id }}" class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 border border-amber-200 dark:border-amber-700 rounded-lg p-6">
                        <div class="flex items-start gap-4">
                            <div class="flex items-center justify-center size-12 rounded-lg bg-amber-200 dark:bg-amber-800/50">
                                <flux:icon :name="$app->platform->icon()" class="size-6 text-amber-700 dark:text-amber-300" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-100 truncate">{{ $app->name }}</h3>
                                    <flux:badge color="amber" size="sm">Recommended</flux:badge>
                                </div>
                                @if ($app->description)
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">{{ $app->description }}</p>
                                @endif
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    <flux:badge color="blue" size="sm">{{ $app->platform->label() }}</flux:badge>
                                    <flux:badge color="purple" size="sm">{{ $app->type->label() }}</flux:badge>
                                    @if ($app->version)
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $app->version }}</span>
                                    @endif
                                </div>
                                @if ($app->downloader_code || $app->short_url)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-2 space-y-1">
                                        @if ($app->downloader_code)
                                            <div>Code: <span class="font-mono font-medium">{{ $app->downloader_code }}</span></div>
                                        @endif
                                        @if ($app->short_url)
                                            <div>URL: <span class="font-mono font-medium">{{ $app->short_url }}</span></div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-4">
                            <flux:button href="{{ $app->download_url }}" target="_blank" variant="primary" class="w-full" icon="arrow-down-tray">
                                Download
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Platform Filter --}}
            <div>
                <flux:select wire:model.live="platformFilter">
                    <option value="">All Platforms</option>
                    @foreach ($this->platforms as $platform)
                        <option value="{{ $platform->value }}">{{ $platform->label() }}</option>
                    @endforeach
                </flux:select>
            </div>

            {{-- Type Filter --}}
            <div>
                <flux:select wire:model.live="typeFilter">
                    <option value="">All Types</option>
                    @foreach ($this->types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </flux:select>
            </div>

            {{-- Clear Filters --}}
            <div class="flex items-center">
                @if ($platformFilter || $typeFilter)
                    <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark">
                        Clear Filters
                    </flux:button>
                @endif
            </div>
        </div>
    </div>

    {{-- All Apps Grid --}}
    <div class="mb-4">
        <flux:heading size="lg">All Apps</flux:heading>
    </div>

    @if ($this->apps->isEmpty())
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <flux:icon.device-phone-mobile class="size-12 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg">No apps available</flux:heading>
            <flux:text variant="muted" class="mt-2">
                @if ($platformFilter || $typeFilter)
                    No apps match your selected filters
                @else
                    No streaming apps are available at the moment
                @endif
            </flux:text>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($this->apps as $app)
                <div wire:key="app-{{ $app->id }}" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6 hover:border-zinc-300 dark:hover:border-zinc-600 transition-colors">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center size-12 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon :name="$app->platform->icon()" class="size-6 text-zinc-600 dark:text-zinc-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100 truncate">{{ $app->name }}</h3>
                                @if ($app->is_recommended)
                                    <flux:badge color="amber" size="sm">Recommended</flux:badge>
                                @endif
                            </div>
                            @if ($app->description)
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">{{ $app->description }}</p>
                            @endif
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <flux:badge color="blue" size="sm">{{ $app->platform->label() }}</flux:badge>
                                <flux:badge color="purple" size="sm">{{ $app->type->label() }}</flux:badge>
                                @if ($app->version)
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $app->version }}</span>
                                @endif
                            </div>
                            @if ($app->downloader_code || $app->short_url)
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-2 space-y-1">
                                    @if ($app->downloader_code)
                                        <div>Code: <span class="font-mono font-medium">{{ $app->downloader_code }}</span></div>
                                    @endif
                                    @if ($app->short_url)
                                        <div>URL: <span class="font-mono font-medium">{{ $app->short_url }}</span></div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="mt-4">
                        <flux:button href="{{ $app->download_url }}" target="_blank" variant="subtle" class="w-full" icon="arrow-down-tray">
                            Download
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Help Text --}}
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
        <div class="flex gap-4">
            <flux:icon.information-circle class="size-6 text-blue-600 dark:text-blue-400 shrink-0" />
            <div>
                <flux:heading size="sm" class="text-blue-900 dark:text-blue-100">How to use these apps</flux:heading>
                <flux:text class="text-blue-800 dark:text-blue-200 mt-2">
                    Download and install the app for your device, then use your subscription credentials to log in.
                    For apps with a "Code", enter it in the Code Downloader on your device.
                    If you need help setting up, contact our support team.
                </flux:text>
            </div>
        </div>
    </div>
</div>

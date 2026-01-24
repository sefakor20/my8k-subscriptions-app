<div class="max-w-7xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Streaming Apps</flux:heading>
            <flux:text variant="muted" class="mt-2">
                Manage downloadable apps for customers to stream content
            </flux:text>
        </div>

        <flux:button wire:click="createApp" variant="primary" icon="plus">
            Add App
        </flux:button>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-6">
            <flux:callout variant="success" icon="check-circle">
                {{ session('success') }}
            </flux:callout>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6">
            <flux:callout variant="danger" icon="exclamation-triangle">
                {{ session('error') }}
            </flux:callout>
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search --}}
            <div>
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Search apps..."
                    icon="magnifying-glass"
                />
            </div>

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

            {{-- Status Filter --}}
            <div class="flex gap-2">
                <flux:button
                    wire:click="$set('activeFilter', null)"
                    :variant="$activeFilter === null ? 'primary' : 'subtle'"
                    size="sm"
                >
                    All
                </flux:button>
                <flux:button
                    wire:click="$set('activeFilter', true)"
                    :variant="$activeFilter === true ? 'primary' : 'subtle'"
                    size="sm"
                >
                    Active
                </flux:button>
                <flux:button
                    wire:click="$set('activeFilter', false)"
                    :variant="$activeFilter === false ? 'primary' : 'subtle'"
                    size="sm"
                >
                    Inactive
                </flux:button>
            </div>
        </div>

        @if ($search || $platformFilter || $typeFilter || $activeFilter !== null)
            <div class="mt-4">
                <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark">
                    Clear Filters
                </flux:button>
            </div>
        @endif
    </div>

    {{-- Data Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            App
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Platform
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Type
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Version
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->apps as $app)
                        <tr wire:key="app-{{ $app->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center size-10 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon :name="$app->platform->icon()" class="size-5 text-zinc-600 dark:text-zinc-400" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $app->name }}</span>
                                            @if ($app->is_recommended)
                                                <flux:badge color="amber" size="sm">Recommended</flux:badge>
                                            @endif
                                        </div>
                                        @if ($app->description)
                                            <div class="text-sm text-zinc-500 dark:text-zinc-400 truncate max-w-xs">
                                                {{ $app->description }}
                                            </div>
                                        @endif
                                        @if ($app->downloader_code)
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Code: <span class="font-mono">{{ $app->downloader_code }}</span>
                                                @if ($app->short_url)
                                                    | URL: <span class="font-mono">{{ $app->short_url }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:badge color="blue" size="sm">{{ $app->platform->label() }}</flux:badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:badge color="purple" size="sm">{{ $app->type->label() }}</flux:badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $app->version ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($app->is_active)
                                    <flux:badge color="green" size="sm">Active</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item wire:click="editApp('{{ $app->id }}')" icon="pencil">
                                            Edit
                                        </flux:menu.item>

                                        <flux:menu.item wire:click="toggleActive('{{ $app->id }}')" icon="arrow-path">
                                            {{ $app->is_active ? 'Deactivate' : 'Activate' }}
                                        </flux:menu.item>

                                        <flux:menu.item wire:click="toggleRecommended('{{ $app->id }}')" icon="star">
                                            {{ $app->is_recommended ? 'Remove Recommendation' : 'Mark as Recommended' }}
                                        </flux:menu.item>

                                        <flux:menu.item href="{{ $app->download_url }}" target="_blank" icon="arrow-down-tray">
                                            Download
                                        </flux:menu.item>

                                        <flux:menu.separator />

                                        <flux:menu.item
                                            wire:click="deleteApp('{{ $app->id }}')"
                                            wire:confirm="Are you sure you want to delete this app?"
                                            icon="trash"
                                            variant="danger"
                                        >
                                            Delete
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <flux:icon.device-phone-mobile class="size-12 text-zinc-400 mb-4" />
                                    <flux:heading size="lg">No streaming apps found</flux:heading>
                                    <flux:text variant="muted" class="mt-2">
                                        @if ($search || $platformFilter || $typeFilter)
                                            No apps match your filters
                                        @else
                                            Add your first streaming app to get started
                                        @endif
                                    </flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($this->apps->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->apps->links() }}
            </div>
        @endif
    </div>

    {{-- Form Modal --}}
    <livewire:admin.streaming-app-form-modal />
</div>

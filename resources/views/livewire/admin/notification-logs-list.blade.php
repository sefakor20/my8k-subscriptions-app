<div class="max-w-7xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Notification Logs</flux:heading>
            <flux:text variant="muted" class="mt-2">
                View all email notifications sent to customers
            </flux:text>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Search --}}
            <div>
                <flux:field>
                    <flux:label>Search</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Subject, email, or name..."
                    />
                </flux:field>
            </div>

            {{-- Category Filter --}}
            <div>
                <flux:field>
                    <flux:label>Category</flux:label>
                    <flux:select wire:model.live="categoryFilter">
                        <option value="">All Categories</option>
                        @foreach ($this->categories as $category)
                            <option value="{{ $category->value }}">{{ $category->label() }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            {{-- Status Filter --}}
            <div>
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model.live="statusFilter">
                        <option value="">All Statuses</option>
                        @foreach ($this->statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            {{-- Date From --}}
            <div>
                <flux:field>
                    <flux:label>From Date</flux:label>
                    <flux:input
                        wire:model.live="dateFrom"
                        type="date"
                    />
                </flux:field>
            </div>

            {{-- Date To --}}
            <div>
                <flux:field>
                    <flux:label>To Date</flux:label>
                    <flux:input
                        wire:model.live="dateTo"
                        type="date"
                    />
                </flux:field>
            </div>
        </div>

        {{-- Reset Button --}}
        <div class="flex items-end mt-4">
            <flux:button wire:click="resetFilters" variant="subtle" icon="arrow-path">
                Reset Filters
            </flux:button>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            User
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Notification
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Category
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Sent At
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->logs as $log)
                        <tr wire:key="log-{{ $log->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $log->user?->name ?? 'Unknown' }}
                                        </div>
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $log->user?->email ?? 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100 max-w-xs truncate">
                                    {{ $log->subject ?? $log->short_type }}
                                </div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $log->short_type }}
                                </div>
                                @if($log->failure_reason)
                                    <div class="text-xs text-red-600 dark:text-red-400 mt-1 truncate max-w-xs" title="{{ $log->failure_reason }}">
                                        {{ Str::limit($log->failure_reason, 50) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:badge :color="$log->category->color()" size="sm">
                                    {{ $log->category->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:badge :color="$log->status->color()" size="sm">
                                    {{ $log->status->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                <div>{{ $log->created_at->format('M d, Y') }}</div>
                                <div class="text-xs">{{ $log->created_at->format('H:i:s') }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <flux:icon.bell-slash class="size-12 mx-auto text-zinc-400 dark:text-zinc-600 mb-4" />
                                <flux:heading size="lg" class="mb-2">No Notification Logs</flux:heading>
                                <flux:text variant="muted">
                                    @if($search || $categoryFilter || $statusFilter || $dateFrom || $dateTo)
                                        No logs match your filters.
                                    @else
                                        No notifications have been sent yet.
                                    @endif
                                </flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($this->logs->hasPages())
            <div class="bg-white dark:bg-zinc-800 px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->logs->links() }}
            </div>
        @endif
    </div>
</div>

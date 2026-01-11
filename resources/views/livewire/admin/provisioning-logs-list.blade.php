<div class="max-w-7xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Provisioning Logs</flux:heading>
            <flux:text variant="muted" class="mt-2">
                Monitor and audit all provisioning activities
            </flux:text>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Status Filter --}}
            <div>
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model.live="statusFilter">
                        <option value="">All Statuses</option>
                        @foreach ($this->statuses as $status)
                            <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            {{-- Action Filter --}}
            <div>
                <flux:field>
                    <flux:label>Action</flux:label>
                    <flux:select wire:model.live="actionFilter">
                        <option value="">All Actions</option>
                        @foreach ($this->actions as $action)
                            <option value="{{ $action['value'] }}">{{ $action['label'] }}</option>
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

        {{-- Search & Actions Row --}}
        <div class="mt-4 flex items-center justify-between gap-4">
            <div class="flex-1">
                <flux:field>
                    <flux:label>Search</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search in error messages, codes, IDs..."
                    />
                </flux:field>
            </div>

            <flux:button wire:click="resetFilters" variant="subtle" icon="arrow-path" class="mt-6">
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
                            Timestamp
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Action
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Subscription
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Attempt
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Error
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->logs as $log)
                        <tr wire:key="log-{{ $log->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $log->created_at->format('M d, Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                <span class="font-medium">{{ ucfirst($log->action->value) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.status-badge :status="$log->status" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($log->subscription)
                                    <div class="text-sm">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $log->subscription->user->name }}
                                        </div>
                                        <div class="text-zinc-500 dark:text-zinc-400">
                                            {{ $log->subscription->plan->name }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                #{{ $log->attempt_number }}
                            </td>
                            <td class="px-6 py-4 max-w-md truncate text-sm text-red-600 dark:text-red-400">
                                @if ($log->error_message)
                                    {{ Str::limit($log->error_message, 50) }}
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item wire:click="showDetail('{{ $log->id }}')" icon="eye">
                                            View Details
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <flux:icon.document-text class="size-12 text-zinc-400 mb-4" />
                                    <flux:heading size="lg">No provisioning logs found</flux:heading>
                                    <flux:text variant="muted" class="mt-2">
                                        Try adjusting your filters
                                    </flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($this->logs->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->logs->links() }}
            </div>
        @endif
    </div>

    {{-- Provisioning Log Detail Modal --}}
    <livewire:admin.provisioning-log-detail-modal />
</div>

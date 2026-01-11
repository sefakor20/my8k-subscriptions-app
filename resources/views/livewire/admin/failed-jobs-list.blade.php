<div class="max-w-7xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Failed Jobs Management</flux:heading>
            <flux:text variant="muted" class="mt-2">
                Monitor and manage failed queue jobs
            </flux:text>
        </div>
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Job Type Filter --}}
            <div>
                <flux:field>
                    <flux:label>Job Type</flux:label>
                    <flux:select wire:model.live="jobTypeFilter">
                        <option value="">All Types</option>
                        @foreach ($this->jobTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            {{-- Error Search --}}
            <div>
                <flux:field>
                    <flux:label>Error Search</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="errorSearch"
                        type="text"
                        placeholder="Search in exceptions..."
                    />
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

        {{-- Actions Row --}}
        <div class="flex items-center justify-between mt-4">
            <flux:button wire:click="resetFilters" variant="subtle" icon="arrow-path">
                Reset Filters
            </flux:button>

            <div class="flex gap-3">
                @if (count($selectedIds) > 0)
                    <flux:button wire:click="retrySelected" variant="primary" size="sm">
                        Retry Selected ({{ count($selectedIds) }})
                    </flux:button>
                    <flux:button wire:click="deleteSelected" variant="danger" size="sm">
                        Delete Selected ({{ count($selectedIds) }})
                    </flux:button>
                @endif

                <flux:button wire:click="retryAll" wire:confirm="Are you sure you want to retry ALL failed jobs?" variant="primary" size="sm">
                    Retry All
                </flux:button>
                <flux:button wire:click="deleteAll" wire:confirm="Are you sure you want to delete ALL failed jobs? This cannot be undone." variant="danger" size="sm">
                    Delete All
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left">
                            <input type="checkbox" wire:model.live="selectAll" wire:click="toggleSelectAll" class="rounded">
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Job ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Queue
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Exception
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Failed At
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:border-zinc-700">
                    @forelse ($this->failedJobs as $job)
                        <tr wire:key="job-{{ $job->uuid }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4">
                                <input type="checkbox" wire:click="toggleSelect('{{ $job->uuid }}')" @checked(in_array($job->uuid, $selectedIds)) class="rounded">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-zinc-900 dark:text-zinc-100">
                                {{ substr($job->uuid, 0, 8) }}...
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $job->queue }}
                            </td>
                            <td class="px-6 py-4 max-w-md truncate text-sm text-zinc-500 dark:text-zinc-400">
                                {{ Str::limit($job->exception, 100) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ \Carbon\Carbon::parse($job->failed_at)->format('M d, Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item wire:click="showDetail('{{ $job->uuid }}')" icon="eye">
                                            View Details
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <flux:icon.check-circle class="size-12 text-green-500 mb-4" />
                                    <flux:heading size="lg">No failed jobs found</flux:heading>
                                    <flux:text variant="muted" class="mt-2">
                                        All jobs are running successfully!
                                    </flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($this->failedJobs->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->failedJobs->links() }}
            </div>
        @endif
    </div>

    {{-- Failed Job Detail Modal --}}
    <livewire:admin.failed-job-detail-modal />
</div>

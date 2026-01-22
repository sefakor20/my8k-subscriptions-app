<div class="max-w-7xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Orders Management</flux:heading>
            <flux:text variant="muted" class="mt-2">
                Manage and monitor all customer orders
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

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Search --}}
            <div>
                <flux:field>
                    <flux:label>Search</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Email or name..."
                    />
                </flux:field>
            </div>

            {{-- Status Filter --}}
            <div>
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model.live="statusFilter">
                        <option value="">All Statuses</option>
                        @foreach ($this->statuses as $status)
                            <option value="{{ $status->value }}">{{ str()->headline($status->value) }}</option>
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
                            Order ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            User
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Plan
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Amount
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Paid At
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->orders as $order)
                        <tr wire:key="order-{{ $order->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                #{{ $order->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $order->user->name }}
                                </div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $order->user->email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $order->subscription?->plan->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                ${{ number_format($order->amount / 100, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.status-badge :status="$order->status" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $order->paid_at?->format('M d, Y H:i') ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item wire:click="showDetail('{{ $order->id }}')" icon="eye">
                                            View Details
                                        </flux:menu.item>

                                        <flux:menu.item wire:click="retryProvisioning('{{ $order->id }}')" icon="arrow-path">
                                            Retry Provisioning
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <flux:icon.inbox class="size-12 text-zinc-400 dark:text-zinc-600 mb-4" />
                                    <flux:heading size="lg">No orders found</flux:heading>
                                    <flux:text variant="muted" class="mt-2">
                                        Try adjusting your filters or search criteria
                                    </flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($this->orders->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->orders->links() }}
            </div>
        @endif
    </div>

    {{-- Order Detail Modal --}}
    <livewire:admin.order-detail-modal />
</div>

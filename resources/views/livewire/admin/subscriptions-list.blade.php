<div class="w-full">
    {{-- Page Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Subscriptions Management</flux:heading>
            <flux:text variant="muted" class="mt-2">
                Manage and monitor all customer subscriptions
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
                            <option value="{{ $status->value }}">{{ $status->value }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            {{-- Plan Filter --}}
            <div>
                <flux:field>
                    <flux:label>Plan</flux:label>
                    <flux:select wire:model.live="planFilter">
                        <option value="">All Plans</option>
                        @foreach ($this->plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
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
        </div>

        {{-- Second Row --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
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

            {{-- Reset Button --}}
            <div class="flex items-end">
                <flux:button wire:click="resetFilters" variant="subtle" icon="arrow-path">
                    Reset Filters
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
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            User
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Plan
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Starts At
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Expires At
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->subscriptions as $subscription)
                        <tr wire:key="subscription-{{ $subscription->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                #{{ $subscription->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $subscription->user->name }}
                                </div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $subscription->user->email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $subscription->plan->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.status-badge :status="$subscription->status" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $subscription->starts_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $subscription->expires_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item wire:click="showDetail({{ $subscription->id }})" icon="eye">
                                            View Details
                                        </flux:menu.item>

                                        <flux:menu.item wire:click="manualProvision({{ $subscription->id }})" icon="arrow-path">
                                            Retry Provisioning
                                        </flux:menu.item>

                                        @if ($subscription->status === \App\Enums\SubscriptionStatus::Active)
                                            <flux:menu.item wire:click="suspend({{ $subscription->id }})" icon="pause-circle">
                                                Suspend
                                            </flux:menu.item>
                                        @elseif ($subscription->status === \App\Enums\SubscriptionStatus::Suspended)
                                            <flux:menu.item wire:click="reactivate({{ $subscription->id }})" icon="play-circle">
                                                Reactivate
                                            </flux:menu.item>
                                        @endif

                                        <flux:menu.separator />

                                        <flux:menu.item wire:click="cancel({{ $subscription->id }})" icon="x-circle" variant="danger">
                                            Cancel Subscription
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
                                    <flux:heading size="lg">No subscriptions found</flux:heading>
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
        @if ($this->subscriptions->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->subscriptions->links() }}
            </div>
        @endif
    </div>

    {{-- Subscription Detail Modal --}}
    <livewire:admin.subscription-detail-modal />
</div>

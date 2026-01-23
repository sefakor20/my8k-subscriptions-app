<div class="w-full">
    {{-- Page Header --}}
    <div class="mb-8">
        <flux:heading size="xl" class="font-bold">My Subscriptions</flux:heading>
        <flux:text variant="muted" class="mt-2">
            Manage your IPTV subscriptions and access your service credentials
        </flux:text>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text variant="muted" class="text-sm">Active</flux:text>
                    <flux:heading size="lg" class="mt-1">{{ $this->statistics['active'] }}</flux:heading>
                </div>
                <flux:icon.check-circle class="w-8 h-8 text-green-500" />
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text variant="muted" class="text-sm">Expiring Soon</flux:text>
                    <flux:heading size="lg" class="mt-1">{{ $this->statistics['expiring_soon'] }}</flux:heading>
                </div>
                <flux:icon.exclamation-triangle class="w-8 h-8 text-yellow-500" />
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text variant="muted" class="text-sm">Expired</flux:text>
                    <flux:heading size="lg" class="mt-1">{{ $this->statistics['expired'] }}</flux:heading>
                </div>
                <flux:icon.x-circle class="w-8 h-8 text-red-500" />
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text variant="muted" class="text-sm">Total</flux:text>
                    <flux:heading size="lg" class="mt-1">{{ $this->statistics['total'] }}</flux:heading>
                </div>
                <flux:icon.squares-2x2 class="w-8 h-8 text-blue-500" />
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <div class="flex items-center gap-4">
            {{-- Status Filter --}}
            <div class="flex-1">
                <flux:field>
                    <flux:label>Filter by Status</flux:label>
                    <flux:select wire:model.live="statusFilter">
                        <option value="">All Statuses</option>
                        @foreach ($this->statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->value }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            {{-- Reset Button --}}
            @if ($statusFilter !== '')
                <div class="flex items-end">
                    <flux:button wire:click="resetFilters" variant="subtle" icon="arrow-path">
                        Reset
                    </flux:button>
                </div>
            @endif
        </div>
    </div>

    {{-- Subscriptions List --}}
    @if ($this->subscriptions->isEmpty())
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <flux:icon.inbox class="w-16 h-16 mx-auto text-zinc-400 dark:text-zinc-600 mb-4" />
            <flux:heading size="lg" class="mb-2">No subscriptions found</flux:heading>
            <flux:text variant="muted">
                @if ($statusFilter !== '')
                    No subscriptions match your filter criteria.
                @else
                    You don't have any subscriptions yet.
                @endif
            </flux:text>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($this->subscriptions as $subscription)
                <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:border-zinc-300 dark:hover:border-zinc-600 transition-colors">
                    <div class="p-6">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <flux:heading size="lg">{{ $subscription->plan->name }}</flux:heading>

                                    {{-- Status Badge --}}
                                    @if ($subscription->status === \App\Enums\SubscriptionStatus::Active)
                                        @if ($subscription->expires_at && now()->diffInDays($subscription->expires_at) <= 7 && $subscription->expires_at->isFuture())
                                            <flux:badge color="yellow" icon="exclamation-triangle">Expiring Soon</flux:badge>
                                        @else
                                            <flux:badge color="green" icon="check-circle">Active</flux:badge>
                                        @endif
                                    @elseif ($subscription->status === \App\Enums\SubscriptionStatus::Expired)
                                        <flux:badge color="red" icon="x-circle">Expired</flux:badge>
                                    @elseif ($subscription->status === \App\Enums\SubscriptionStatus::Suspended)
                                        <flux:badge color="yellow" icon="pause">Suspended</flux:badge>
                                    @elseif ($subscription->status === \App\Enums\SubscriptionStatus::Cancelled)
                                        <flux:badge color="red" icon="x-mark">Cancelled</flux:badge>
                                    @elseif ($subscription->status === \App\Enums\SubscriptionStatus::Pending)
                                        <flux:badge color="blue" icon="clock">Pending</flux:badge>
                                    @endif
                                </div>

                                <div class="space-y-2">
                                    @if ($subscription->plan->description)
                                        <flux:text variant="muted" class="text-sm">
                                            {{ $subscription->plan->description }}
                                        </flux:text>
                                    @endif

                                    <div class="flex flex-wrap gap-4 text-sm">
                                        @if ($subscription->created_at)
                                            <div class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
                                                <flux:icon.calendar class="w-4 h-4" />
                                                <span>Started: {{ $subscription->created_at->format('M d, Y') }}</span>
                                            </div>
                                        @endif

                                        @if ($subscription->expires_at)
                                            <div class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
                                                <flux:icon.clock class="w-4 h-4" />
                                                @if ($subscription->expires_at->isFuture())
                                                    <span>Expires: {{ $subscription->expires_at->format('M d, Y') }} ({{ $subscription->expires_at->diffForHumans() }})</span>
                                                @else
                                                    <span>Expired: {{ $subscription->expires_at->format('M d, Y') }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($subscription->serviceAccount)
                                            <div class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
                                                <flux:icon.server class="w-4 h-4" />
                                                <span>Account: {{ $subscription->serviceAccount->username }}</span>
                                            </div>
                                        @endif

                                        {{-- Auto-renewal status --}}
                                        @if ($subscription->status === \App\Enums\SubscriptionStatus::Active)
                                            <div class="flex items-center gap-1.5 {{ $subscription->auto_renew ? 'text-green-600 dark:text-green-400' : 'text-zinc-500 dark:text-zinc-500' }}">
                                                <flux:icon.arrow-path class="w-4 h-4" />
                                                @if ($subscription->auto_renew)
                                                    <span>Auto-renew: On</span>
                                                    @if ($subscription->next_renewal_at || $subscription->expires_at)
                                                        <span class="text-zinc-500 dark:text-zinc-500">
                                                            ({{ ($subscription->next_renewal_at ?? $subscription->expires_at->subDay())->format('M d') }})
                                                        </span>
                                                    @endif
                                                @else
                                                    <span>Auto-renew: Off</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    @if ($subscription->plan->features)
                                        <div class="flex flex-wrap gap-2 mt-3">
                                            @foreach ($subscription->plan->features as $feature)
                                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 rounded text-xs">
                                                    <flux:icon.check class="w-3 h-3" />
                                                    {{ $feature }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2 lg:ml-6">
                                @if ($subscription->status === \App\Enums\SubscriptionStatus::Active && $subscription->expires_at?->isFuture())
                                    <flux:button
                                        wire:click="changePlan('{{ $subscription->id }}')"
                                        variant="subtle"
                                        size="sm"
                                        icon="arrows-right-left"
                                    >
                                        Change Plan
                                    </flux:button>
                                @endif
                                <flux:button
                                    wire:click="showDetail('{{ $subscription->id }}')"
                                    variant="primary"
                                    size="sm"
                                    icon="eye"
                                >
                                    View Details
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $this->subscriptions->links() }}
        </div>
    @endif

    {{-- Subscription Detail Modal --}}
    <livewire:dashboard.subscription-detail />

    {{-- Change Plan Modal --}}
    <livewire:dashboard.change-plan-modal />
</div>

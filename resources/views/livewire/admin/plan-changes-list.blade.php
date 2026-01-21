<div class="max-w-7xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Plan Changes</flux:heading>
            <flux:text variant="muted" class="mt-2">
                View and manage subscription plan changes
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
            <flux:callout variant="danger" icon="x-circle">
                {{ session('error') }}
            </flux:callout>
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Search --}}
            <div>
                <flux:field>
                    <flux:label>Search</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Customer name, email, or plan..."
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
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            {{-- Type Filter --}}
            <div>
                <flux:field>
                    <flux:label>Type</flux:label>
                    <flux:select wire:model.live="typeFilter">
                        <option value="">All Types</option>
                        <option value="upgrade">Upgrade</option>
                        <option value="downgrade">Downgrade</option>
                    </flux:select>
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
                            Customer
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            From Plan
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            To Plan
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Type
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Amount
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Created
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->planChanges as $planChange)
                        <tr wire:key="plan-change-{{ $planChange->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $planChange->user->name }}
                                </div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $planChange->user->email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $planChange->fromPlan->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $planChange->toPlan->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($planChange->isUpgrade())
                                    <flux:badge color="green" icon="arrow-trending-up">Upgrade</flux:badge>
                                @else
                                    <flux:badge color="yellow" icon="arrow-trending-down">Downgrade</flux:badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($planChange->status === \App\Enums\PlanChangeStatus::Completed)
                                    <flux:badge color="green" icon="check-circle">{{ $planChange->status->label() }}</flux:badge>
                                @elseif ($planChange->status === \App\Enums\PlanChangeStatus::Scheduled)
                                    <flux:badge color="blue" icon="clock">{{ $planChange->status->label() }}</flux:badge>
                                @elseif ($planChange->status === \App\Enums\PlanChangeStatus::Pending)
                                    <flux:badge color="yellow" icon="arrow-path">{{ $planChange->status->label() }}</flux:badge>
                                @elseif ($planChange->status === \App\Enums\PlanChangeStatus::Failed)
                                    <flux:badge color="red" icon="x-circle">{{ $planChange->status->label() }}</flux:badge>
                                @elseif ($planChange->status === \App\Enums\PlanChangeStatus::Cancelled)
                                    <flux:badge icon="minus-circle">{{ $planChange->status->label() }}</flux:badge>
                                @else
                                    <flux:badge>{{ $planChange->status->label() }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if ($planChange->proration_amount > 0)
                                    <span class="text-green-600 dark:text-green-400">{{ $planChange->formattedProrationAmount() }}</span>
                                @elseif ($planChange->credit_amount > 0)
                                    <span class="text-blue-600 dark:text-blue-400">{{ $planChange->formattedCreditAmount() }} credit</span>
                                @else
                                    <span class="text-zinc-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $planChange->created_at->format('M d, Y H:i') }}
                                @if ($planChange->scheduled_at)
                                    <div class="text-xs">Scheduled: {{ $planChange->scheduled_at->format('M d, Y') }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @if ($planChange->canBeCancelled())
                                    <flux:button
                                        wire:click="cancelPlanChange('{{ $planChange->id }}')"
                                        wire:confirm="Are you sure you want to cancel this plan change?"
                                        variant="ghost"
                                        size="sm"
                                        icon="x-mark"
                                    >
                                        Cancel
                                    </flux:button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <flux:icon.arrows-right-left class="size-12 text-zinc-400 dark:text-zinc-600 mb-4" />
                                    <flux:heading size="lg">No plan changes found</flux:heading>
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
        @if ($this->planChanges->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->planChanges->links() }}
            </div>
        @endif
    </div>
</div>

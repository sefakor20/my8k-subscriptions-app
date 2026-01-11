<div class="max-w-7xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Plans Management</flux:heading>
            <flux:text variant="muted" class="mt-2">
                Manage IPTV subscription plans
            </flux:text>
        </div>

        <flux:button wire:click="createPlan" variant="primary" icon="plus">
            Create Plan
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
        <div class="flex gap-3">
            <flux:button
                wire:click="filterActive(null)"
                :variant="$activeFilter === null ? 'primary' : 'subtle'"
                size="sm"
            >
                All Plans
            </flux:button>
            <flux:button
                wire:click="filterActive(true)"
                :variant="$activeFilter === true ? 'primary' : 'subtle'"
                size="sm"
            >
                Active Only
            </flux:button>
            <flux:button
                wire:click="filterActive(false)"
                :variant="$activeFilter === false ? 'primary' : 'subtle'"
                size="sm"
            >
                Inactive Only
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
                            Name
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Price
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Billing
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Duration
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            WooCommerce ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Subscriptions
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->plans as $plan)
                        <tr wire:key="plan-{{ $plan->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $plan->name }}
                                    </div>
                                    <div class="text-zinc-500 dark:text-zinc-400 font-mono text-xs">
                                        {{ $plan->slug }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                <span class="font-medium">{{ $plan->formattedPrice() }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ ucfirst($plan->billing_interval->value) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $plan->duration_days }} days
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-zinc-500 dark:text-zinc-400">
                                {{ $plan->woocommerce_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($plan->is_active)
                                    <span class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-900/20 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-400 ring-1 ring-inset ring-green-600/20 dark:ring-green-500/30">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-zinc-50 dark:bg-zinc-900 px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-400 ring-1 ring-inset ring-zinc-500/10 dark:ring-zinc-400/20">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $plan->subscriptions_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item wire:click="editPlan('{{ $plan->id }}')" icon="pencil">
                                            Edit
                                        </flux:menu.item>

                                        <flux:menu.item wire:click="toggleActive('{{ $plan->id }}')" icon="arrow-path">
                                            {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                                        </flux:menu.item>

                                        @if ($this->canDelete($plan->id))
                                            <flux:menu.separator />
                                            <flux:menu.item
                                                wire:click="deletePlan('{{ $plan->id }}')"
                                                wire:confirm="Are you sure you want to delete this plan? This action cannot be undone."
                                                icon="trash"
                                                variant="danger"
                                            >
                                                Delete
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.separator />
                                            <flux:menu.item disabled icon="lock-closed">
                                                Cannot Delete ({{ $plan->subscriptions_count }} subscriptions)
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <flux:icon.tag class="size-12 text-zinc-400 mb-4" />
                                    <flux:heading size="lg">No plans found</flux:heading>
                                    <flux:text variant="muted" class="mt-2">
                                        Create your first plan to get started
                                    </flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Plan Form Modal --}}
    <livewire:admin.plan-form-modal />
</div>

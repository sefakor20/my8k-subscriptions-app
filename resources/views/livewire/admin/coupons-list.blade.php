<div class="max-w-7xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Coupons Management</flux:heading>
            <flux:text variant="muted" class="mt-2">
                Manage promotional discount codes
            </flux:text>
        </div>

        <flux:button wire:click="createCoupon" variant="primary" icon="plus">
            Create Coupon
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
        <div class="flex flex-col sm:flex-row gap-4">
            {{-- Search --}}
            <div class="flex-1 max-w-md">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Search by code or name..."
                    icon="magnifying-glass"
                />
            </div>

            {{-- Status Filter --}}
            <div class="flex gap-3">
                <flux:button
                    wire:click="filterActive(null)"
                    :variant="$activeFilter === null ? 'primary' : 'subtle'"
                    size="sm"
                >
                    All
                </flux:button>
                <flux:button
                    wire:click="filterActive(true)"
                    :variant="$activeFilter === true ? 'primary' : 'subtle'"
                    size="sm"
                >
                    Active
                </flux:button>
                <flux:button
                    wire:click="filterActive(false)"
                    :variant="$activeFilter === false ? 'primary' : 'subtle'"
                    size="sm"
                >
                    Inactive
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
                            Code / Name
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Discount
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Validity
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Usage
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Plans
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
                    @forelse ($this->coupons as $coupon)
                        <tr wire:key="coupon-{{ $coupon->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ $coupon->code }}
                                    </div>
                                    <div class="text-zinc-500 dark:text-zinc-400 text-xs">
                                        {{ $coupon->name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                <span class="font-medium">{{ $coupon->formattedDiscount() }}</span>
                                @if ($coupon->discount_type->value === 'trial_extension')
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        +{{ $coupon->trial_extension_days }} days
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if ($coupon->valid_from || $coupon->valid_until)
                                    <div class="text-zinc-900 dark:text-zinc-100">
                                        @if ($coupon->valid_from)
                                            {{ $coupon->valid_from->format('M d, Y') }}
                                        @else
                                            -
                                        @endif
                                    </div>
                                    <div class="text-zinc-500 dark:text-zinc-400 text-xs">
                                        to
                                        @if ($coupon->valid_until)
                                            {{ $coupon->valid_until->format('M d, Y') }}
                                        @else
                                            No end
                                        @endif
                                    </div>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">Always valid</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="text-zinc-900 dark:text-zinc-100">
                                    {{ $coupon->redemptions_count }} used
                                </div>
                                @if ($coupon->max_redemptions)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        of {{ $coupon->max_redemptions }} max
                                    </div>
                                @else
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Unlimited
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($coupon->plans->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($coupon->plans->take(2) as $plan)
                                            <span class="inline-flex items-center rounded-md bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-400">
                                                {{ $plan->name }}
                                            </span>
                                        @endforeach
                                        @if ($coupon->plans->count() > 2)
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                +{{ $coupon->plans->count() - 2 }} more
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">All plans</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($coupon->isExpired())
                                    <span class="inline-flex items-center rounded-md bg-red-50 dark:bg-red-900/20 px-2 py-1 text-xs font-medium text-red-700 dark:text-red-400 ring-1 ring-inset ring-red-600/20 dark:ring-red-500/30">
                                        Expired
                                    </span>
                                @elseif ($coupon->isExhausted())
                                    <span class="inline-flex items-center rounded-md bg-amber-50 dark:bg-amber-900/20 px-2 py-1 text-xs font-medium text-amber-700 dark:text-amber-400 ring-1 ring-inset ring-amber-600/20 dark:ring-amber-500/30">
                                        Exhausted
                                    </span>
                                @elseif ($coupon->is_active)
                                    <span class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-900/20 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-400 ring-1 ring-inset ring-green-600/20 dark:ring-green-500/30">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-zinc-50 dark:bg-zinc-900 px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-400 ring-1 ring-inset ring-zinc-500/10 dark:ring-zinc-400/20">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item wire:click="editCoupon('{{ $coupon->id }}')" icon="pencil">
                                            Edit
                                        </flux:menu.item>

                                        <flux:menu.item wire:click="toggleActive('{{ $coupon->id }}')" icon="arrow-path">
                                            {{ $coupon->is_active ? 'Deactivate' : 'Activate' }}
                                        </flux:menu.item>

                                        @if ($this->canDelete($coupon->id))
                                            <flux:menu.separator />
                                            <flux:menu.item
                                                wire:click="deleteCoupon('{{ $coupon->id }}')"
                                                wire:confirm="Are you sure you want to delete this coupon? This action cannot be undone."
                                                icon="trash"
                                                variant="danger"
                                            >
                                                Delete
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.separator />
                                            <flux:menu.item disabled icon="lock-closed">
                                                Cannot Delete ({{ $coupon->redemptions_count }} redemptions)
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <flux:icon.ticket class="size-12 text-zinc-400 mb-4" />
                                    <flux:heading size="lg">No coupons found</flux:heading>
                                    <flux:text variant="muted" class="mt-2">
                                        @if ($search)
                                            No coupons match your search criteria
                                        @else
                                            Create your first coupon to get started
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
        @if ($this->coupons->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->coupons->links() }}
            </div>
        @endif
    </div>

    {{-- Coupon Form Modal --}}
    <livewire:admin.coupon-form-modal />
</div>

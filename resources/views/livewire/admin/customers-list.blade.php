<div class="w-full">
    {{-- Page Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Customers</flux:heading>
            <flux:text variant="muted" class="mt-2">
                View and manage all customer accounts
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

            {{-- Email Status Filter --}}
            <div>
                <flux:field>
                    <flux:label>Email Status</flux:label>
                    <flux:select wire:model.live="verifiedFilter">
                        <option value="">All</option>
                        <option value="verified">Verified</option>
                        <option value="unverified">Unverified</option>
                    </flux:select>
                </flux:field>
            </div>

            {{-- Role Filter --}}
            <div>
                <flux:field>
                    <flux:label>Role</flux:label>
                    <flux:select wire:model.live="roleFilter">
                        <option value="">All Users</option>
                        <option value="admin">Admins</option>
                        <option value="customer">Customers</option>
                    </flux:select>
                </flux:field>
            </div>

            {{-- Date From --}}
            <div>
                <flux:field>
                    <flux:label>Registered From</flux:label>
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
                    <flux:label>Registered To</flux:label>
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
                            Customer
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Email Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Subscriptions
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Orders
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Total Spent
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Registered
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <flux:avatar :initials="$customer->initials()" size="sm" />
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $customer->name }}
                                            </span>
                                            @if ($customer->is_admin)
                                                <flux:badge color="purple" size="sm">Admin</flux:badge>
                                            @endif
                                        </div>
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $customer->email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($customer->email_verified_at)
                                    <flux:badge color="green" size="sm">Verified</flux:badge>
                                @else
                                    <flux:badge color="amber" size="sm">Unverified</flux:badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $customer->subscriptions_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $customer->orders_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if ($customer->orders_sum_amount)
                                    {{ number_format($customer->orders_sum_amount, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $customer->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item wire:click="showDetail('{{ $customer->id }}')" icon="eye">
                                            View Details
                                        </flux:menu.item>

                                        @if ($customer->id !== auth()->id())
                                            <flux:menu.item wire:click="toggleAdmin('{{ $customer->id }}')" icon="shield-check">
                                                {{ $customer->is_admin ? 'Revoke Admin' : 'Grant Admin' }}
                                            </flux:menu.item>

                                            <flux:menu.separator />

                                            <flux:menu.item wire:click="impersonate('{{ $customer->id }}')" icon="user">
                                                Login as User
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
                                    <flux:icon.users class="size-12 text-zinc-400 dark:text-zinc-600 mb-4" />
                                    <flux:heading size="lg">No customers found</flux:heading>
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
        @if ($this->customers->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->customers->links() }}
            </div>
        @endif
    </div>

    {{-- Customer Detail Modal --}}
    <livewire:admin.customer-detail-modal />
</div>

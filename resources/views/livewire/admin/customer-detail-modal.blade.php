<flux:modal wire:model.self="show" @close="closeModal" class="max-w-4xl">
    @if ($this->customer)
        <div class="p-6">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <flux:avatar :initials="$this->customer->initials()" size="lg" />
                    <div>
                        <div class="flex items-center gap-2">
                            <flux:heading size="lg">{{ $this->customer->name }}</flux:heading>
                            @if ($this->customer->is_admin)
                                <flux:badge color="purple" size="sm">Admin</flux:badge>
                            @endif
                        </div>
                        <flux:text variant="muted" class="mt-1">
                            {{ $this->customer->email }}
                        </flux:text>
                    </div>
                </div>
                @if ($this->customer->email_verified_at)
                    <flux:badge color="green" size="sm">Email Verified</flux:badge>
                @else
                    <flux:badge color="amber" size="sm">Email Unverified</flux:badge>
                @endif
            </div>

            {{-- Content Grid --}}
            <div class="space-y-6">
                {{-- Account Information --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Account Information</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between">
                            <flux:text variant="muted">Registered</flux:text>
                            <flux:text>{{ $this->customer->created_at->format('M d, Y H:i') }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Email Verified</flux:text>
                            <flux:text>
                                @if ($this->customer->email_verified_at)
                                    {{ $this->customer->email_verified_at->format('M d, Y H:i') }}
                                @else
                                    Not verified
                                @endif
                            </flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Two-Factor Auth</flux:text>
                            <flux:text>
                                @if ($this->customer->two_factor_confirmed_at)
                                    <flux:badge color="green" size="sm">Enabled</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Disabled</flux:badge>
                                @endif
                            </flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Role</flux:text>
                            <flux:text>{{ $this->customer->is_admin ? 'Administrator' : 'Customer' }}</flux:text>
                        </div>
                    </div>
                </div>

                {{-- Statistics --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Statistics</flux:heading>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 text-center">
                            <flux:heading size="xl">{{ $this->customer->subscriptions_count }}</flux:heading>
                            <flux:text variant="muted" size="sm">Subscriptions</flux:text>
                        </div>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 text-center">
                            <flux:heading size="xl">{{ $this->customer->orders_count }}</flux:heading>
                            <flux:text variant="muted" size="sm">Orders</flux:text>
                        </div>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 text-center">
                            <flux:heading size="xl">{{ $this->customer->invoices_count }}</flux:heading>
                            <flux:text variant="muted" size="sm">Invoices</flux:text>
                        </div>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 text-center">
                            <flux:heading size="xl">
                                @if ($this->customer->orders_sum_amount)
                                    {{ number_format($this->customer->orders_sum_amount, 2) }}
                                @else
                                    0.00
                                @endif
                            </flux:heading>
                            <flux:text variant="muted" size="sm">Total Spent</flux:text>
                        </div>
                    </div>
                </div>

                {{-- Recent Subscriptions --}}
                @if ($this->customer->subscriptions->isNotEmpty())
                    <div>
                        <flux:heading size="sm" class="mb-3">Recent Subscriptions</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-3">
                            @foreach ($this->customer->subscriptions as $subscription)
                                <div class="flex justify-between items-center">
                                    <div>
                                        <flux:text>{{ $subscription->plan->name }}</flux:text>
                                        <flux:text variant="muted" size="sm">
                                            {{ $subscription->starts_at->format('M d, Y') }} - {{ $subscription->expires_at->format('M d, Y') }}
                                        </flux:text>
                                    </div>
                                    <x-admin.status-badge :status="$subscription->status" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div>
                        <flux:heading size="sm" class="mb-3">Subscriptions</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                            <flux:text variant="muted">No subscriptions yet</flux:text>
                        </div>
                    </div>
                @endif

                {{-- Recent Orders --}}
                @if ($this->customer->orders->isNotEmpty())
                    <div>
                        <flux:heading size="sm" class="mb-3">Recent Orders</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-3">
                            @foreach ($this->customer->orders as $order)
                                <div class="flex justify-between items-center">
                                    <div>
                                        <flux:text>{{ $order->formattedAmount() }}</flux:text>
                                        <flux:text variant="muted" size="sm">
                                            {{ $order->created_at->format('M d, Y H:i') }}
                                        </flux:text>
                                    </div>
                                    <x-admin.status-badge :status="$order->status" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div>
                        <flux:heading size="sm" class="mb-3">Orders</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                            <flux:text variant="muted">No orders yet</flux:text>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-between mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex gap-3">
                    @if ($this->customer->id !== auth()->id())
                        <flux:button wire:click="toggleAdmin" variant="subtle" icon="shield-check">
                            {{ $this->customer->is_admin ? 'Revoke Admin Access' : 'Grant Admin Access' }}
                        </flux:button>

                        <flux:button wire:click="impersonate" variant="primary" icon="user">
                            Login as User
                        </flux:button>
                    @endif
                </div>

                <flux:button wire:click="closeModal" variant="ghost">
                    Close
                </flux:button>
            </div>
        </div>
    @endif
</flux:modal>

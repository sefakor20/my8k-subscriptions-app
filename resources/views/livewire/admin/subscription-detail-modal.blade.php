<flux:modal :open="$show" @close="closeModal" class="max-w-4xl">
    @if ($this->subscription)
        <div class="p-6">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <flux:heading size="lg">Subscription #{{ $this->subscription->id }}</flux:heading>
                    <flux:text variant="muted" class="mt-1">
                        Detailed information and management actions
                    </flux:text>
                </div>
                <x-admin.status-badge :status="$this->subscription->status" />
            </div>

            {{-- Content Grid --}}
            <div class="space-y-6">
                {{-- User Information --}}
                <div>
                    <flux:heading size="sm" class="mb-3">User Information</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between">
                            <flux:text variant="muted">Name</flux:text>
                            <flux:text>{{ $this->subscription->user->name }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Email</flux:text>
                            <flux:text>{{ $this->subscription->user->email }}</flux:text>
                        </div>
                    </div>
                </div>

                {{-- Subscription Details --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Subscription Details</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between">
                            <flux:text variant="muted">Plan</flux:text>
                            <flux:text>{{ $this->subscription->plan->name }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Starts At</flux:text>
                            <flux:text>{{ $this->subscription->starts_at->format('M d, Y H:i') }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Expires At</flux:text>
                            <flux:text>{{ $this->subscription->expires_at->format('M d, Y H:i') }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Created</flux:text>
                            <flux:text>{{ $this->subscription->created_at->diffForHumans() }}</flux:text>
                        </div>
                    </div>
                </div>

                {{-- Service Account --}}
                @if ($this->subscription->serviceAccount)
                    <div>
                        <flux:heading size="sm" class="mb-3">Service Account Credentials</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between items-center">
                                <flux:text variant="muted">Username</flux:text>
                                <div class="flex items-center gap-2">
                                    <flux:text class="font-mono">{{ $this->subscription->serviceAccount->username }}</flux:text>
                                    <flux:button
                                        size="xs"
                                        variant="ghost"
                                        icon="clipboard"
                                        onclick="navigator.clipboard.writeText('{{ $this->subscription->serviceAccount->username }}')"
                                        title="Copy to clipboard"
                                    />
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <flux:text variant="muted">Password</flux:text>
                                <div class="flex items-center gap-2">
                                    <flux:text class="font-mono">
                                        @if ($showPassword)
                                            {{ $this->subscription->serviceAccount->password }}
                                        @else
                                            ••••••••••••
                                        @endif
                                    </flux:text>
                                    <flux:button
                                        size="xs"
                                        variant="ghost"
                                        :icon="$showPassword ? 'eye-slash' : 'eye'"
                                        wire:click="togglePassword"
                                        title="Toggle password visibility"
                                    />
                                    @if ($showPassword)
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            icon="clipboard"
                                            onclick="navigator.clipboard.writeText('{{ $this->subscription->serviceAccount->password }}')"
                                            title="Copy to clipboard"
                                        />
                                    @endif
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <flux:text variant="muted">Status</flux:text>
                                <x-admin.status-badge :status="$this->subscription->serviceAccount->status" />
                            </div>
                        </div>
                    </div>
                @else
                    <div>
                        <flux:heading size="sm" class="mb-3">Service Account</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                            <flux:text variant="muted">No service account provisioned yet</flux:text>
                        </div>
                    </div>
                @endif

                {{-- Recent Orders --}}
                @if ($this->subscription->orders->isNotEmpty())
                    <div>
                        <flux:heading size="sm" class="mb-3">Recent Orders ({{ $this->subscription->orders->count() }})</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                            @foreach ($this->subscription->orders as $order)
                                <div class="flex justify-between items-center">
                                    <div>
                                        <flux:text>Order #{{ $order->id }}</flux:text>
                                        <flux:text variant="muted" size="sm">{{ $order->created_at->format('M d, Y H:i') }}</flux:text>
                                    </div>
                                    <x-admin.status-badge :status="$order->status" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Recent Provisioning Logs --}}
                @if ($this->subscription->provisioningLogs->isNotEmpty())
                    <div>
                        <flux:heading size="sm" class="mb-3">Recent Provisioning Activity ({{ $this->subscription->provisioningLogs->count() }})</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                            @foreach ($this->subscription->provisioningLogs as $log)
                                <div class="flex justify-between items-center">
                                    <div>
                                        <flux:text>{{ $log->action->value }} - Attempt #{{ $log->attempt }}</flux:text>
                                        <flux:text variant="muted" size="sm">{{ $log->created_at->diffForHumans() }}</flux:text>
                                    </div>
                                    <flux:badge :variant="$log->status === 'success' ? 'success' : 'danger'" size="sm">
                                        {{ $log->status }}
                                    </flux:badge>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Extend Subscription Form --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Extend Subscription</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <div class="flex items-end gap-4">
                            <div class="flex-1">
                                <flux:field>
                                    <flux:label>Number of Days</flux:label>
                                    <flux:input
                                        type="number"
                                        wire:model="extendDays"
                                        min="1"
                                        max="365"
                                    />
                                </flux:field>
                            </div>
                            <flux:button wire:click="extend" variant="primary">
                                Extend Subscription
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-between mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex gap-3">
                    <flux:button wire:click="retryProvisioning" variant="primary">
                        <flux:icon.arrow-path class="size-5" />
                        Retry Provisioning
                    </flux:button>

                    @if ($this->subscription->status === \App\Enums\SubscriptionStatus::Active)
                        <flux:button wire:click="suspend" variant="subtle">
                            <flux:icon.pause-circle class="size-5" />
                            Suspend
                        </flux:button>
                    @elseif ($this->subscription->status === \App\Enums\SubscriptionStatus::Suspended)
                        <flux:button wire:click="reactivate" variant="subtle">
                            <flux:icon.play-circle class="size-5" />
                            Reactivate
                        </flux:button>
                    @endif
                </div>

                <div class="flex gap-3">
                    <flux:button wire:click="cancel" variant="danger">
                        <flux:icon.x-circle class="size-5" />
                        Cancel Subscription
                    </flux:button>

                    <flux:button wire:click="closeModal" variant="ghost">
                        Close
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</flux:modal>

<flux:modal wire:model.self="show" @close="closeModal" class="max-w-4xl">
    @if ($this->order)
        <div class="p-6">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <flux:heading size="lg">Order #{{ $this->order->id }}</flux:heading>
                    <flux:text variant="muted" class="mt-1">
                        WooCommerce Order: {{ $this->order->woocommerce_order_id ?? 'N/A' }}
                    </flux:text>
                </div>
                <x-admin.status-badge :status="$this->order->status" />
            </div>

            {{-- Content Grid --}}
            <div class="space-y-6">
                {{-- User Information --}}
                <div>
                    <flux:heading size="sm" class="mb-3">User Information</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between">
                            <flux:text variant="muted">Name</flux:text>
                            <flux:text>{{ $this->order->user->name }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Email</flux:text>
                            <flux:text>{{ $this->order->user->email }}</flux:text>
                        </div>
                    </div>
                </div>

                {{-- Order Details --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Order Details</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between">
                            <flux:text variant="muted">Amount</flux:text>
                            <flux:text>${{ number_format($this->order->amount / 100, 2) }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Currency</flux:text>
                            <flux:text>{{ strtoupper($this->order->currency) }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Paid At</flux:text>
                            <flux:text>{{ $this->order->paid_at?->format('M d, Y H:i') ?? 'N/A' }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Created</flux:text>
                            <flux:text>{{ $this->order->created_at->diffForHumans() }}</flux:text>
                        </div>
                    </div>
                </div>

                {{-- Subscription Information --}}
                @if ($this->order->subscription)
                    <div>
                        <flux:heading size="sm" class="mb-3">Subscription Information</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between">
                                <flux:text variant="muted">Plan</flux:text>
                                <flux:text>{{ $this->order->subscription->plan->name }}</flux:text>
                            </div>
                            <div class="flex justify-between">
                                <flux:text variant="muted">Status</flux:text>
                                <x-admin.status-badge :status="$this->order->subscription->status" />
                            </div>
                            <div class="flex justify-between">
                                <flux:text variant="muted">Expires At</flux:text>
                                <flux:text>{{ $this->order->subscription->expires_at->format('M d, Y') }}</flux:text>
                            </div>
                        </div>
                    </div>

                    {{-- Service Account --}}
                    @if ($this->order->subscription->serviceAccount)
                        <div>
                            <flux:heading size="sm" class="mb-3">Service Account</flux:heading>
                            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                                <div class="flex justify-between">
                                    <flux:text variant="muted">Username</flux:text>
                                    <flux:text class="font-mono">{{ $this->order->subscription->serviceAccount->username }}</flux:text>
                                </div>
                                <div class="flex justify-between">
                                    <flux:text variant="muted">Status</flux:text>
                                    <x-admin.status-badge :status="$this->order->subscription->serviceAccount->status" />
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Recent Provisioning Logs --}}
                    @if ($this->order->subscription->provisioningLogs->isNotEmpty())
                        <div>
                            <flux:heading size="sm" class="mb-3">Recent Provisioning Activity</flux:heading>
                            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                                @foreach ($this->order->subscription->provisioningLogs as $log)
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <flux:text>{{ $log->action->value }} - Attempt #{{ $log->attempt }}</flux:text>
                                            <flux:text variant="muted" size="sm">{{ $log->created_at->diffForHumans() }}</flux:text>
                                        </div>
                                        <flux:badge :color="$log->status === 'success' ? 'green' : 'red'" size="sm">
                                            {{ $log->status }}
                                        </flux:badge>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif

                {{-- Webhook Payload --}}
                @if ($this->order->webhook_payload)
                    <div>
                        <flux:heading size="sm" class="mb-3">Webhook Payload</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 overflow-auto max-h-64">
                            <pre class="text-xs text-zinc-900 dark:text-zinc-100"><code>{{ json_encode($this->order->webhook_payload, JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-between mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button wire:click="retryProvisioning" variant="primary" icon="arrow-path">
                    Retry Provisioning
                </flux:button>

                <flux:button wire:click="closeModal" variant="ghost">
                    Close
                </flux:button>
            </div>
        </div>
    @endif
</flux:modal>

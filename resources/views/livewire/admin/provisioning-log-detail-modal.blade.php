<flux:modal wire:model.self="show" @close="closeModal" class="max-w-5xl">
    @if ($log)
        <div class="p-6">
            {{-- Header --}}
            <div class="mb-6">
                <flux:heading size="lg">Provisioning Log Details</flux:heading>
                <flux:text variant="muted" class="mt-1 font-mono text-sm">
                    ID: {{ $log->id }}
                </flux:text>
            </div>

            {{-- Content --}}
            <div class="space-y-6">
                {{-- Log Metadata --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Log Information</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between">
                            <flux:text variant="muted">Action</flux:text>
                            <flux:text class="font-medium">{{ ucfirst($log->action->value) }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Status</flux:text>
                            <x-admin.status-badge :status="$log->status" />
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Attempt Number</flux:text>
                            <flux:text>#{{ $log->attempt_number }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Duration</flux:text>
                            <flux:text>{{ $log->getDurationSeconds() }}s ({{ $log->duration_ms }}ms)</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Created At</flux:text>
                            <flux:text>{{ $log->created_at->format('M d, Y H:i:s') }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Job ID</flux:text>
                            <flux:text class="font-mono text-xs">{{ $log->job_id ?? 'N/A' }}</flux:text>
                        </div>
                    </div>
                </div>

                {{-- Subscription/Order Info --}}
                @if ($log->subscription || $log->order)
                    <div>
                        <flux:heading size="sm" class="mb-3">Related Records</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                            @if ($log->subscription)
                                <div class="flex justify-between">
                                    <flux:text variant="muted">Subscription ID</flux:text>
                                    <flux:text class="font-mono text-xs">{{ $log->subscription_id }}</flux:text>
                                </div>
                                <div class="flex justify-between">
                                    <flux:text variant="muted">User</flux:text>
                                    <flux:text>{{ $log->subscription->user->name }} ({{ $log->subscription->user->email }})</flux:text>
                                </div>
                                <div class="flex justify-between">
                                    <flux:text variant="muted">Plan</flux:text>
                                    <flux:text>{{ $log->subscription->plan->name }}</flux:text>
                                </div>
                            @endif
                            @if ($log->order)
                                <div class="flex justify-between">
                                    <flux:text variant="muted">Order ID</flux:text>
                                    <flux:text class="font-mono text-xs">{{ $log->order_id }}</flux:text>
                                </div>
                            @endif
                            @if ($log->serviceAccount)
                                <div class="flex justify-between">
                                    <flux:text variant="muted">Service Account</flux:text>
                                    <flux:text>{{ $log->serviceAccount->username }}</flux:text>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Error Information --}}
                @if ($log->error_message || $log->error_code)
                    <div>
                        <flux:heading size="sm" class="mb-3">Error Information</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                            @if ($log->error_code)
                                <div class="flex justify-between">
                                    <flux:text variant="muted">Error Code</flux:text>
                                    <flux:text class="font-mono text-xs text-red-600 dark:text-red-400">{{ $log->error_code }}</flux:text>
                                </div>
                            @endif
                            @if ($log->error_message)
                                <div>
                                    <flux:text variant="muted" class="mb-2">Error Message</flux:text>
                                    <div class="bg-white dark:bg-zinc-900 rounded p-3 overflow-auto max-h-40">
                                        <pre class="text-xs text-red-600 dark:text-red-400 whitespace-pre-wrap">{{ $log->error_message }}</pre>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- My8K Request --}}
                @if ($log->my8k_request)
                    <div>
                        <flux:heading size="sm" class="mb-3">My8K API Request</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 overflow-auto max-h-64">
                            <pre class="text-xs text-zinc-900 dark:text-zinc-100"><code>{{ json_encode($log->my8k_request, JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                    </div>
                @endif

                {{-- My8K Response --}}
                @if ($log->my8k_response)
                    <div>
                        <flux:heading size="sm" class="mb-3">My8K API Response</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 overflow-auto max-h-64">
                            <pre class="text-xs text-zinc-900 dark:text-zinc-100"><code>{{ json_encode($log->my8k_response, JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button wire:click="closeModal" variant="ghost">
                    Close
                </flux:button>
            </div>
        </div>
    @endif
</flux:modal>

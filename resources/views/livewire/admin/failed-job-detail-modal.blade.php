<flux:modal wire:model.self="show" @close="closeModal" class="max-w-5xl">
    @if ($job)
        <div class="p-6">
            {{-- Header --}}
            <div class="mb-6">
                <flux:heading size="lg">Failed Job Details</flux:heading>
                <flux:text variant="muted" class="mt-1 font-mono text-sm">
                    UUID: {{ $job->uuid }}
                </flux:text>
            </div>

            {{-- Content --}}
            <div class="space-y-6">
                {{-- Job Metadata --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Job Information</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between">
                            <flux:text variant="muted">Connection</flux:text>
                            <flux:text>{{ $job->connection }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Queue</flux:text>
                            <flux:text>{{ $job->queue }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Failed At</flux:text>
                            <flux:text>{{ \Carbon\Carbon::parse($job->failed_at)->format('M d, Y H:i:s') }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text variant="muted">Failed</flux:text>
                            <flux:text>{{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}</flux:text>
                        </div>
                    </div>
                </div>

                {{-- Exception --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Exception</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 overflow-auto max-h-96">
                        <pre class="text-xs text-red-600 dark:text-red-400 whitespace-pre-wrap">{{ $job->exception }}</pre>
                    </div>
                </div>

                {{-- Payload --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Job Payload</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 overflow-auto max-h-64">
                        <pre class="text-xs text-zinc-900 dark:text-zinc-100"><code>{{ $job->payload }}</code></pre>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-between mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex gap-3">
                    <flux:button wire:click="retry" variant="primary" icon="arrow-path">
                        Retry Job
                    </flux:button>

                    <flux:button wire:click="delete" wire:confirm="Are you sure you want to delete this job?" variant="danger" icon="trash">
                        Delete Job
                    </flux:button>
                </div>

                <flux:button wire:click="closeModal" variant="ghost">
                    Close
                </flux:button>
            </div>
        </div>
    @endif
</flux:modal>

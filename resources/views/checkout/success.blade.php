<x-layouts.app>
    <div class="max-w-lg mx-auto py-16 px-4 text-center">
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-8">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-6">
                <flux:icon.check-circle class="w-10 h-10 text-green-600 dark:text-green-400" />
            </div>

            <flux:heading size="xl" class="font-bold mb-2">Payment Successful!</flux:heading>

            <flux:text variant="muted" class="mb-6">
                Your payment has been processed successfully. Your subscription is being activated.
            </flux:text>

            @if (isset($reference))
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 mb-6">
                    <flux:text variant="muted" class="text-sm">Reference</flux:text>
                    <flux:text class="font-mono text-sm">{{ $reference }}</flux:text>
                </div>
            @endif

            <flux:text variant="muted" class="text-sm mb-6">
                You will receive a confirmation email shortly with your subscription details.
            </flux:text>

            <div class="flex flex-col gap-3">
                <flux:button variant="primary" href="{{ route('dashboard') }}" class="w-full">
                    Go to Dashboard
                </flux:button>
                <flux:button variant="ghost" href="{{ route('orders.index') }}" class="w-full">
                    View My Orders
                </flux:button>
            </div>
        </div>
    </div>
</x-layouts.app>

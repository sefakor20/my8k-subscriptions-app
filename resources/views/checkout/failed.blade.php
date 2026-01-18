<x-layouts.app>
    <div class="max-w-lg mx-auto py-16 px-4 text-center">
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-8">
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mx-auto mb-6">
                <flux:icon.x-circle class="w-10 h-10 text-red-600 dark:text-red-400" />
            </div>

            <flux:heading size="xl" class="font-bold mb-2">Payment Failed</flux:heading>

            <flux:text variant="muted" class="mb-6">
                We were unable to process your payment. Please try again or use a different payment method.
            </flux:text>

            @if (isset($error))
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
                    <flux:text class="text-red-600 dark:text-red-400 text-sm">
                        {{ $error }}
                    </flux:text>
                </div>
            @endif

            @if (isset($reference))
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 mb-6">
                    <flux:text variant="muted" class="text-sm">Reference</flux:text>
                    <flux:text class="font-mono text-sm">{{ $reference }}</flux:text>
                </div>
            @endif

            <div class="flex flex-col gap-3">
                <flux:button variant="primary" href="{{ route('checkout.index') }}" class="w-full">
                    Try Again
                </flux:button>
                <flux:button variant="ghost" href="{{ route('dashboard') }}" class="w-full">
                    Go to Dashboard
                </flux:button>
            </div>
        </div>
    </div>
</x-layouts.app>

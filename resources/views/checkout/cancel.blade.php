<x-layouts.app>
    <div class="max-w-lg mx-auto py-16 px-4 text-center">
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-8">
            <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center mx-auto mb-6">
                <flux:icon.x-circle class="w-10 h-10 text-yellow-600 dark:text-yellow-400" />
            </div>

            <flux:heading size="xl" class="font-bold mb-2">Payment Cancelled</flux:heading>

            <flux:text variant="muted" class="mb-6">
                You have cancelled the payment process. No charges have been made to your account.
            </flux:text>

            <div class="flex flex-col gap-3">
                <flux:button variant="primary" href="{{ route('checkout.index') }}" class="w-full">
                    Choose a Plan
                </flux:button>
                <flux:button variant="ghost" href="{{ route('dashboard') }}" class="w-full">
                    Go to Dashboard
                </flux:button>
            </div>
        </div>
    </div>
</x-layouts.app>

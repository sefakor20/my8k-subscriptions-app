<div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 border border-zinc-200 dark:border-zinc-700">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <flux:icon.credit-card class="size-6 text-zinc-500 dark:text-zinc-400" />
            <flux:heading size="lg" class="font-semibold">Reseller Credits</flux:heading>
        </div>
        <flux:button size="sm" variant="ghost" wire:click="refreshBalance" wire:loading.attr="disabled">
            <flux:icon.arrow-path class="size-4" wire:loading.class="animate-spin" />
        </flux:button>
    </div>

    {{-- Current Balance with Alert Level --}}
    <div class="mb-4">
        @php
            $alertLevel = $this->metrics['alertLevel'];
            $alertColors = [
                'ok' => 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
                'warning' => 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
                'critical' => 'text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800',
                'urgent' => 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
            ];
            $colorClass = $alertColors[$alertLevel] ?? $alertColors['ok'];
        @endphp

        <div class="px-4 py-3 rounded-lg border {{ $colorClass }}">
            <div class="flex items-baseline justify-between">
                <div>
                    <flux:text variant="muted" size="sm">Current Balance</flux:text>
                    <div class="text-3xl font-bold mt-1">
                        {{ number_format($this->metrics['currentBalance'], 0) }}
                    </div>
                    <flux:text variant="muted" size="xs">credits</flux:text>
                </div>
                @if($alertLevel !== 'ok')
                    <div class="flex items-center gap-1">
                        <flux:icon.exclamation-triangle class="size-5" />
                        <flux:text size="xs" class="font-medium uppercase">
                            {{ ucfirst($alertLevel) }}
                        </flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Metrics Grid --}}
    <div class="grid grid-cols-2 gap-4 mb-4">
        {{-- 24h Change --}}
        <div>
            <flux:text variant="muted" size="xs">24h Change</flux:text>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-lg font-semibold {{ $this->metrics['change24h'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                    {{ $this->metrics['change24h'] >= 0 ? '+' : '' }}{{ number_format($this->metrics['change24h'], 0) }}
                </span>
            </div>
        </div>

        {{-- 7d Change --}}
        <div>
            <flux:text variant="muted" size="xs">7d Change</flux:text>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-lg font-semibold {{ $this->metrics['change7d'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                    {{ $this->metrics['change7d'] >= 0 ? '+' : '' }}{{ number_format($this->metrics['change7d'], 0) }}
                </span>
            </div>
        </div>

        {{-- Avg Daily Usage --}}
        <div>
            <flux:text variant="muted" size="xs">Avg Daily Usage</flux:text>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($this->metrics['avgDailyUsage'], 1) }}
                </span>
                <flux:text variant="muted" size="xs">credits/day</flux:text>
            </div>
        </div>

        {{-- Depletion Estimate --}}
        <div>
            <flux:text variant="muted" size="xs">Depletion Est.</flux:text>
            <div class="flex items-baseline gap-1 mt-1">
                @if($this->metrics['estimatedDepletionDays'])
                    <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                        ~{{ $this->metrics['estimatedDepletionDays'] }}
                    </span>
                    <flux:text variant="muted" size="xs">days</flux:text>
                @else
                    <span class="text-lg font-semibold text-zinc-500 dark:text-zinc-400">N/A</span>
                @endif
            </div>
        </div>
    </div>

    {{-- View Details Link --}}
    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
        <flux:button variant="ghost" size="sm" :href="route('admin.credits')" wire:navigate class="w-full justify-center">
            View Detailed Report
            <flux:icon.arrow-right class="size-4 ml-1" />
        </flux:button>
    </div>
</div>

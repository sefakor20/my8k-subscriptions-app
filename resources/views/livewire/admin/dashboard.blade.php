<div class="w-full" wire:poll.60s>
    {{-- Page Header --}}
    <div class="mb-8">
        <flux:heading size="xl" class="font-bold">Admin Dashboard</flux:heading>
        <flux:text variant="muted" class="mt-2">
            Overview of your IPTV provisioning service statistics and metrics
        </flux:text>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Active Subscriptions --}}
        <x-admin.stat-card
            title="Active Subscriptions"
            :value="number_format($this->activeSubscriptions)"
            icon="users"
            color="blue"
        />

        {{-- Orders Today --}}
        <x-admin.stat-card
            title="Orders Today"
            :value="number_format($this->ordersToday)"
            icon="shopping-cart"
            color="green"
        />

        {{-- Success Rate --}}
        <x-admin.stat-card
            title="Success Rate (24h)"
            :value="number_format($this->successRate, 1) . '%'"
            icon="check-circle"
            color="green"
        />

        {{-- Failed Jobs --}}
        <x-admin.stat-card
            title="Failed Jobs"
            :value="number_format($this->failedJobs)"
            icon="exclamation-triangle"
            :color="$this->failedJobs > 0 ? 'red' : 'green'"
        />
    </div>

    {{-- Auto-refresh Indicator --}}
    <div class="mt-6 flex items-center justify-end">
        <flux:text variant="muted" size="sm" class="flex items-center gap-2">
            <flux:icon.arrow-path class="size-4" />
            Auto-refreshing every 60 seconds
        </flux:text>
    </div>
</div>

<div class="w-full" @if($autoRefresh) wire:poll.60s="refreshData" @endif>
    {{-- Page Header --}}
    <div class="mb-8 flex items-start justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Analytics Dashboard</flux:heading>
            <flux:text variant="muted" class="mt-2">
                Comprehensive analytics and performance metrics for your IPTV provisioning service
            </flux:text>
        </div>

        {{-- Controls --}}
        <div class="flex items-center gap-3">
            {{-- Date Range Dropdown --}}
            <flux:dropdown>
                <flux:button variant="ghost" icon:trailing="chevron-down" size="sm">
                    @if($dateRangeType === 'custom')
                        {{ $customStartDate }} - {{ $customEndDate }}
                    @else
                        Last {{ $dateRange }} Days
                    @endif
                </flux:button>
                <flux:menu>
                    <flux:menu.item wire:click="setPresetDateRange(7)">
                        Last 7 Days
                    </flux:menu.item>
                    <flux:menu.item wire:click="setPresetDateRange(14)">
                        Last 14 Days
                    </flux:menu.item>
                    <flux:menu.item wire:click="setPresetDateRange(30)">
                        Last 30 Days
                    </flux:menu.item>
                    <flux:menu.item wire:click="setPresetDateRange(60)">
                        Last 60 Days
                    </flux:menu.item>
                    <flux:menu.item wire:click="setPresetDateRange(90)">
                        Last 90 Days
                    </flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item icon="calendar" x-on:click="$refs.customDateModal.showModal()">
                        Custom Range...
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            {{-- Actions Dropdown --}}
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" size="sm" />
                <flux:menu>
                    <flux:menu.item wire:click="exportCsv" icon="arrow-down-tray">
                        Export CSV
                    </flux:menu.item>
                    <flux:menu.item wire:click="refreshData" icon="arrow-path">
                        Refresh Data
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>

        {{-- Custom Date Range Modal --}}
        <dialog x-ref="customDateModal" class="rounded-lg bg-white dark:bg-zinc-900 p-6 shadow-xl backdrop:bg-black/50">
            <form method="dialog" class="space-y-4">
                <flux:heading size="lg">Custom Date Range</flux:heading>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:field>
                            <flux:label>Start Date</flux:label>
                            <flux:input type="date" wire:model="customStartDate" />
                        </flux:field>
                    </div>
                    <div>
                        <flux:field>
                            <flux:label>End Date</flux:label>
                            <flux:input type="date" wire:model="customEndDate" />
                        </flux:field>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <flux:button type="button" variant="ghost" onclick="this.closest('dialog').close()">
                        Cancel
                    </flux:button>
                    <flux:button
                        type="button"
                        variant="primary"
                        wire:click="applyCustomDateRange"
                        onclick="this.closest('dialog').close()"
                    >
                        Apply
                    </flux:button>
                </div>
            </form>
        </dialog>
    </div>

    {{-- Performance Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        {{-- Total Provisioned --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" size="sm">Total Provisioned</flux:text>
                <flux:icon.server class="size-5 text-blue-500" />
            </div>
            <flux:heading size="xl">{{ number_format($this->performanceMetrics()['totalProvisioned']) }}</flux:heading>
        </div>

        {{-- Success Rate --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" size="sm">Success Rate</flux:text>
                <flux:icon.check-circle class="size-5 text-green-500" />
            </div>
            <flux:heading size="xl" class="text-green-600 dark:text-green-400">
                {{ number_format($this->performanceMetrics()['successRate'], 1) }}%
            </flux:heading>
        </div>

        {{-- Failure Rate --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" size="sm">Failure Rate</flux:text>
                <flux:icon.x-circle class="size-5 text-red-500" />
            </div>
            <flux:heading size="xl" class="text-red-600 dark:text-red-400">
                {{ number_format($this->performanceMetrics()['failureRate'], 1) }}%
            </flux:heading>
        </div>

        {{-- Avg Duration --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" size="sm">Avg Duration</flux:text>
                <flux:icon.clock class="size-5 text-purple-500" />
            </div>
            <flux:heading size="xl">{{ number_format($this->performanceMetrics()['avgDuration'], 1) }}s</flux:heading>
        </div>

        {{-- Pending --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" size="sm">Pending</flux:text>
                <flux:icon.arrow-path class="size-5 text-amber-500" />
            </div>
            <flux:heading size="xl">{{ number_format($this->performanceMetrics()['pendingCount']) }}</flux:heading>
        </div>
    </div>

    {{-- Charts Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Success Rate Over Time Chart --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-4">Provisioning Success Rate</flux:heading>
            <flux:text variant="muted" size="sm" class="mb-4">
                Daily success rate percentage over the selected period
            </flux:text>
            <div class="h-64">
                @if(empty($this->successRateData['labels']) || empty($this->successRateData['data']))
                    <div class="flex items-center justify-center h-full text-zinc-500">
                        No success rate data available yet
                    </div>
                @else
                    <div
                        x-data="successRateChart(@js($this->successRateData))"
                        x-init="initChart()"
                        class="h-full"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Order Status Distribution Chart --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-4">Order Status Distribution</flux:heading>
            <flux:text variant="muted" size="sm" class="mb-4">
                Distribution of all orders by status
            </flux:text>
            <div class="h-64">
                @if(empty($this->orderStatusData['labels']) || empty($this->orderStatusData['data']))
                    <div class="flex items-center justify-center h-full text-zinc-500">
                        No order data available yet
                    </div>
                @else
                    <div
                        x-data="orderStatusChart(@js($this->orderStatusData))"
                        x-init="initChart()"
                        class="h-full"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Subscription Growth Chart --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-4">Subscription Growth</flux:heading>
            <flux:text variant="muted" size="sm" class="mb-4">
                Cumulative subscription count over time
            </flux:text>
            <div class="h-64">
                @if(empty($this->subscriptionGrowthData['labels']) || empty($this->subscriptionGrowthData['data']))
                    <div class="flex items-center justify-center h-full text-zinc-500">
                        No subscription data available yet
                    </div>
                @else
                    <div
                        x-data="subscriptionGrowthChart(@js($this->subscriptionGrowthData))"
                        x-init="initChart()"
                        class="h-full"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Revenue Over Time Chart --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-4">Revenue Trend</flux:heading>
            <flux:text variant="muted" size="sm" class="mb-4">
                Daily revenue from provisioned orders
            </flux:text>
            <div class="h-64">
                @if(empty($this->revenueData['labels']) || empty($this->revenueData['data']))
                    <div class="flex items-center justify-center h-full text-zinc-500">
                        No revenue data available yet
                    </div>
                @else
                    <div
                        x-data="revenueChart(@js($this->revenueData))"
                        x-init="initChart()"
                        class="h-full"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Error Frequency Chart (Full Width) --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <flux:heading size="lg" class="mb-4">Top Error Types</flux:heading>
        <flux:text variant="muted" size="sm" class="mb-4">
            Most frequent provisioning errors over the selected period
        </flux:text>
        <div class="h-80">
            @if(empty($this->errorFrequencyData['labels']) || empty($this->errorFrequencyData['data']))
                <div class="flex items-center justify-center h-full text-zinc-500">
                    No error data available yet
                </div>
            @else
                <div
                    x-data="errorFrequencyChart(@js($this->errorFrequencyData))"
                    x-init="initChart()"
                    class="h-full"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            @endif
        </div>
    </div>

    {{-- Auto-refresh Indicator --}}
    <div class="flex items-center justify-end">
        <flux:text variant="muted" size="sm" class="flex items-center gap-2">
            @if($autoRefresh)
                <flux:icon.arrow-path class="size-4 animate-spin" />
                Auto-refreshing every 60 seconds
            @else
                <flux:icon.pause class="size-4" />
                Auto-refresh disabled
            @endif
        </flux:text>
    </div>

    {{-- Chart.js Scripts --}}
    @assets
        <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    @endassets

    @script
        <script>
            // Success Rate Chart
            Alpine.data('successRateChart', (initialData) => ({
                chart: null,
                data: initialData,

                initChart() {
                    // Wait for Chart.js to be available
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.initChart(), 50);
                        return;
                    }

                    const ctx = this.$refs.canvas.getContext('2d');
                    const isDark = document.documentElement.classList.contains('dark');

                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: this.data.labels,
                            datasets: [{
                                label: 'Success Rate (%)',
                                data: this.data.data,
                                borderColor: '#22c55e',
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    ticks: {
                                        callback: (value) => value + '%',
                                        color: isDark ? '#a1a1aa' : '#71717a',
                                    },
                                    grid: { color: isDark ? '#27272a' : '#e4e4e7' }
                                },
                                x: {
                                    ticks: { color: isDark ? '#a1a1aa' : '#71717a' },
                                    grid: { display: false }
                                }
                            },
                            plugins: {
                                legend: { display: false }
                            }
                        }
                    });
                }
            }));

            // Order Status Chart
            Alpine.data('orderStatusChart', (initialData) => ({
                chart: null,
                data: initialData,

                initChart() {
                    // Wait for Chart.js to be available
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.initChart(), 50);
                        return;
                    }

                    const ctx = this.$refs.canvas.getContext('2d');
                    const isDark = document.documentElement.classList.contains('dark');

                    this.chart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: this.data.labels,
                            datasets: [{
                                data: this.data.data,
                                backgroundColor: [
                                    '#22c55e',   // Green
                                    '#fbbf24',   // Amber
                                    '#ef4444',   // Red
                                    '#9333ea',   // Purple
                                    '#3b82f6',   // Blue
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: isDark ? '#a1a1aa' : '#71717a' }
                                }
                            }
                        }
                    });
                }
            }));

            // Subscription Growth Chart
            Alpine.data('subscriptionGrowthChart', (initialData) => ({
                chart: null,
                data: initialData,

                initChart() {
                    // Wait for Chart.js to be available
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.initChart(), 50);
                        return;
                    }

                    const ctx = this.$refs.canvas.getContext('2d');
                    const isDark = document.documentElement.classList.contains('dark');

                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: this.data.labels,
                            datasets: [{
                                label: 'Total Subscriptions',
                                data: this.data.data,
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { color: isDark ? '#a1a1aa' : '#71717a' },
                                    grid: { color: isDark ? '#27272a' : '#e4e4e7' }
                                },
                                x: {
                                    ticks: { color: isDark ? '#a1a1aa' : '#71717a' },
                                    grid: { display: false }
                                }
                            },
                            plugins: {
                                legend: { display: false }
                            }
                        }
                    });
                }
            }));

            // Revenue Chart
            Alpine.data('revenueChart', (initialData) => ({
                chart: null,
                data: initialData,

                initChart() {
                    // Wait for Chart.js to be available
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.initChart(), 50);
                        return;
                    }

                    const ctx = this.$refs.canvas.getContext('2d');
                    const isDark = document.documentElement.classList.contains('dark');

                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: this.data.labels,
                            datasets: [{
                                label: 'Revenue ($)',
                                data: this.data.data,
                                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                                borderColor: '#22c55e',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: (value) => '$' + value.toLocaleString(),
                                        color: isDark ? '#a1a1aa' : '#71717a',
                                    },
                                    grid: { color: isDark ? '#27272a' : '#e4e4e7' }
                                },
                                x: {
                                    ticks: { color: isDark ? '#a1a1aa' : '#71717a' },
                                    grid: { display: false }
                                }
                            },
                            plugins: {
                                legend: { display: false }
                            }
                        }
                    });
                }
            }));

            // Error Frequency Chart
            Alpine.data('errorFrequencyChart', (initialData) => ({
                chart: null,
                data: initialData,

                initChart() {
                    // Wait for Chart.js to be available
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.initChart(), 50);
                        return;
                    }

                    const ctx = this.$refs.canvas.getContext('2d');
                    const isDark = document.documentElement.classList.contains('dark');

                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: this.data.labels,
                            datasets: [{
                                label: 'Occurrences',
                                data: this.data.data,
                                backgroundColor: 'rgba(239, 68, 68, 0.8)',
                                borderColor: '#ef4444',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    ticks: { color: isDark ? '#a1a1aa' : '#71717a' },
                                    grid: { color: isDark ? '#27272a' : '#e4e4e7' }
                                },
                                y: {
                                    ticks: { color: isDark ? '#a1a1aa' : '#71717a' },
                                    grid: { display: false }
                                }
                            },
                            plugins: {
                                legend: { display: false }
                            }
                        }
                    });
                }
            }));
        </script>
    @endscript
</div>

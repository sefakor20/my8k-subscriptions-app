<div class="w-full" @if($autoRefresh) wire:poll.60s="refreshData" @endif>
    {{-- Page Header --}}
    <div class="mb-8 flex items-start justify-between">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <flux:button variant="ghost" href="{{ route('admin.coupons.index') }}" icon="arrow-left" size="sm">
                    Back to Coupons
                </flux:button>
            </div>
            <flux:heading size="xl" class="font-bold">Coupon Analytics</flux:heading>
            <flux:text variant="muted" class="mt-2">
                Track coupon usage, discount impact, and performance metrics
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

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        {{-- Total Redemptions --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" size="sm">Total Redemptions</flux:text>
                <flux:icon.ticket class="size-5 text-blue-500" />
            </div>
            <flux:heading size="xl">{{ number_format($this->metrics['totalRedemptions']) }}</flux:heading>
        </div>

        {{-- Total Discount Given --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" size="sm">Total Discounts</flux:text>
                <flux:icon.receipt-percent class="size-5 text-red-500" />
            </div>
            <flux:heading size="xl" class="text-red-600 dark:text-red-400">
                ${{ number_format($this->metrics['totalDiscountGiven'], 2) }}
            </flux:heading>
        </div>

        {{-- Revenue After Discounts --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" size="sm">Revenue (After Discounts)</flux:text>
                <flux:icon.banknotes class="size-5 text-green-500" />
            </div>
            <flux:heading size="xl" class="text-green-600 dark:text-green-400">
                ${{ number_format($this->metrics['revenueAfterDiscounts'], 2) }}
            </flux:heading>
        </div>

        {{-- Avg Discount Per Use --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" size="sm">Avg Discount/Use</flux:text>
                <flux:icon.calculator class="size-5 text-purple-500" />
            </div>
            <flux:heading size="xl">${{ number_format($this->metrics['avgDiscountPerUse'], 2) }}</flux:heading>
        </div>

        {{-- Active Coupons --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" size="sm">Active Coupons</flux:text>
                <flux:icon.check-badge class="size-5 text-amber-500" />
            </div>
            <flux:heading size="xl">{{ number_format($this->metrics['activeCoupons']) }}</flux:heading>
        </div>
    </div>

    {{-- Charts Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Redemptions Over Time Chart --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-4">Redemptions Over Time</flux:heading>
            <flux:text variant="muted" size="sm" class="mb-4">
                Daily coupon redemption count
            </flux:text>
            <div class="h-64">
                @if(empty($this->redemptionsData['data']) || array_sum($this->redemptionsData['data']) === 0)
                    <div class="flex items-center justify-center h-full text-zinc-500">
                        No redemption data available yet
                    </div>
                @else
                    <div
                        x-data="redemptionsChart(@js($this->redemptionsData))"
                        x-init="initChart()"
                        class="h-full"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Discount Amount Over Time Chart --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-4">Discount Amount Over Time</flux:heading>
            <flux:text variant="muted" size="sm" class="mb-4">
                Daily discount amounts given
            </flux:text>
            <div class="h-64">
                @if(empty($this->discountData['data']) || array_sum($this->discountData['data']) === 0)
                    <div class="flex items-center justify-center h-full text-zinc-500">
                        No discount data available yet
                    </div>
                @else
                    <div
                        x-data="discountChart(@js($this->discountData))"
                        x-init="initChart()"
                        class="h-full"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Discount Type Distribution Chart --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-4">Discount Type Distribution</flux:heading>
            <flux:text variant="muted" size="sm" class="mb-4">
                Breakdown by discount type
            </flux:text>
            <div class="h-64">
                @if(empty($this->discountTypeData['labels']) || empty($this->discountTypeData['data']))
                    <div class="flex items-center justify-center h-full text-zinc-500">
                        No discount type data available yet
                    </div>
                @else
                    <div
                        x-data="discountTypeChart(@js($this->discountTypeData))"
                        x-init="initChart()"
                        class="h-full"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Currency Distribution Chart --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-4">Discounts by Currency</flux:heading>
            <flux:text variant="muted" size="sm" class="mb-4">
                Total discount amounts by currency
            </flux:text>
            <div class="h-64">
                @if(empty($this->currencyData['labels']) || empty($this->currencyData['data']))
                    <div class="flex items-center justify-center h-full text-zinc-500">
                        No currency data available yet
                    </div>
                @else
                    <div
                        x-data="currencyChart(@js($this->currencyData))"
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

    {{-- Top Coupons Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <flux:heading size="lg" class="mb-4">Top Performing Coupons</flux:heading>
        <flux:text variant="muted" size="sm" class="mb-4">
            Most used coupons in the selected period
        </flux:text>

        @if(empty($this->topCouponsData))
            <div class="flex flex-col items-center justify-center py-12 text-zinc-500">
                <flux:icon.ticket class="size-12 mb-4 text-zinc-400" />
                <flux:text variant="muted">No coupon redemptions in this period</flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Rank
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Code
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Redemptions
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Total Discount
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Avg Discount
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->topCouponsData as $index => $coupon)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium
                                        @if($index === 0) bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400
                                        @elseif($index === 1) bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300
                                        @elseif($index === 2) bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400
                                        @else bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400
                                        @endif">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ $coupon['code'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                    {{ $coupon['name'] }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ number_format($coupon['redemptions']) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-red-600 dark:text-red-400">
                                    ${{ number_format($coupon['total_discount'], 2) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-zinc-600 dark:text-zinc-400">
                                    ${{ number_format($coupon['avg_discount'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
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
            // Redemptions Chart (Line)
            Alpine.data('redemptionsChart', (initialData) => ({
                chart: null,
                data: initialData,

                initChart() {
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
                                label: 'Redemptions',
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
                            plugins: { legend: { display: false } }
                        }
                    });
                }
            }));

            // Discount Amount Chart (Bar)
            Alpine.data('discountChart', (initialData) => ({
                chart: null,
                data: initialData,

                initChart() {
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
                                label: 'Discount ($)',
                                data: this.data.data,
                                backgroundColor: 'rgba(239, 68, 68, 0.8)',
                                borderColor: '#ef4444',
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
                            plugins: { legend: { display: false } }
                        }
                    });
                }
            }));

            // Discount Type Distribution Chart (Doughnut)
            Alpine.data('discountTypeChart', (initialData) => ({
                chart: null,
                data: initialData,

                initChart() {
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
                                    '#22c55e',   // Green - Percentage
                                    '#3b82f6',   // Blue - Fixed Amount
                                    '#9333ea',   // Purple - Trial Extension
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

            // Currency Distribution Chart (Horizontal Bar)
            Alpine.data('currencyChart', (initialData) => ({
                chart: null,
                data: initialData,

                initChart() {
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
                                label: 'Total Discount',
                                data: this.data.data,
                                backgroundColor: [
                                    'rgba(59, 130, 246, 0.8)',
                                    'rgba(34, 197, 94, 0.8)',
                                    'rgba(249, 115, 22, 0.8)',
                                    'rgba(147, 51, 234, 0.8)',
                                ],
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
                                    ticks: {
                                        callback: (value) => '$' + value.toLocaleString(),
                                        color: isDark ? '#a1a1aa' : '#71717a',
                                    },
                                    grid: { color: isDark ? '#27272a' : '#e4e4e7' }
                                },
                                y: {
                                    ticks: { color: isDark ? '#a1a1aa' : '#71717a' },
                                    grid: { display: false }
                                }
                            },
                            plugins: { legend: { display: false } }
                        }
                    });
                }
            }));
        </script>
    @endscript
</div>

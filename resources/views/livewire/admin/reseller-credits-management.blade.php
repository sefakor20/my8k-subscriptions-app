<div class="w-full">
    {{-- Page Header --}}
    <div class="mb-8 flex items-start justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Reseller Credits Management</flux:heading>
            <flux:text variant="muted" class="mt-2">
                Monitor your My8K reseller credit balance, usage patterns, and transaction history
            </flux:text>
        </div>
        <div class="flex items-center gap-3">
            {{-- Date Range Filter --}}
            <flux:select wire:model.live="dateRange" size="sm">
                <option value="7">Last 7 Days</option>
                <option value="14">Last 14 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="60">Last 60 Days</option>
                <option value="90">Last 90 Days</option>
            </flux:select>

            {{-- Refresh Button --}}
            <flux:button size="sm" wire:click="refreshBalance"  icon="arrow-path">
                Refresh
            </flux:button>
        </div>
    </div>

    {{-- Error Alerts --}}
    @if($metricsError)
        <div class="mb-6">
            <flux:callout variant="danger" class="flex items-center justify-between">
                <div>
                    <div class="font-semibold">Unable to Load Metrics</div>
                    <p class="text-sm mt-1">{{ $metricsError }}</p>
                </div>
                <flux:button wire:click="refreshBalance" size="sm" variant="outline">
                    Retry
                </flux:button>
            </flux:callout>
        </div>
    @endif

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Current Balance --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" size="sm">Current Balance</flux:text>
                @php
                    $alertLevel = $this->metrics['alertLevel'];
                    $badgeColors = [
                        'ok' => 'green',
                        'warning' => 'yellow',
                        'critical' => 'orange',
                        'urgent' => 'red',
                    ];
                @endphp
                @if($alertLevel !== 'ok' && !$metricsError)
                    <flux:badge size="sm" :color="$badgeColors[$alertLevel]">{{ ucfirst($alertLevel) }}</flux:badge>
                @endif
            </div>
            <div class="text-3xl font-bold {{ $metricsError ? 'text-zinc-500 dark:text-zinc-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                {{ $metricsError ? 'N/A' : number_format($this->metrics['currentBalance'], 0) }}
            </div>
            <flux:text variant="muted" size="xs">credits available</flux:text>
        </div>

        {{-- 24h Change --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 border border-zinc-200 dark:border-zinc-700">
            <flux:text variant="muted" size="sm" class="mb-2">24h Change</flux:text>
            @if($metricsError)
                <div class="text-3xl font-bold text-zinc-500 dark:text-zinc-400">N/A</div>
                <flux:text variant="muted" size="xs">data unavailable</flux:text>
            @else
                <div class="text-3xl font-bold {{ $this->metrics['change24h'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                    {{ $this->metrics['change24h'] >= 0 ? '+' : '' }}{{ number_format($this->metrics['change24h'], 0) }}
                </div>
                <flux:text variant="muted" size="xs">credits {{ $this->metrics['change24h'] < 0 ? 'used' : 'added' }}</flux:text>
            @endif
        </div>

        {{-- Avg Daily Usage --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 border border-zinc-200 dark:border-zinc-700">
            <flux:text variant="muted" size="sm" class="mb-2">Avg Daily Usage</flux:text>
            @if($metricsError)
                <div class="text-3xl font-bold text-zinc-500 dark:text-zinc-400">N/A</div>
                <flux:text variant="muted" size="xs">data unavailable</flux:text>
            @else
                <div class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($this->metrics['avgDailyUsage'], 1) }}
                </div>
                <flux:text variant="muted" size="xs">credits per day</flux:text>
            @endif
        </div>

        {{-- Depletion Estimate --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 border border-zinc-200 dark:border-zinc-700">
            <flux:text variant="muted" size="sm" class="mb-2">Depletion Estimate</flux:text>
            @if($metricsError || !$this->metrics['estimatedDepletionDays'])
                <div class="text-3xl font-bold text-zinc-500 dark:text-zinc-400">N/A</div>
                <flux:text variant="muted" size="xs">{{ $metricsError ? 'data unavailable' : 'insufficient data' }}</flux:text>
            @else
                <div class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                    ~{{ $this->metrics['estimatedDepletionDays'] }}
                </div>
                <flux:text variant="muted" size="xs">days remaining</flux:text>
            @endif
        </div>
    </div>

    {{-- Alert Thresholds Info --}}
    <div class="mb-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <flux:icon.information-circle class="size-5 text-blue-600 dark:text-blue-400 mt-0.5" />
            <div class="flex-1">
                <flux:text class="font-medium text-blue-900 dark:text-blue-100">Alert Thresholds</flux:text>
                <flux:text size="sm" variant="muted" class="mt-1">
                    Automated email alerts are triggered at:
                    <span class="font-medium text-yellow-700 dark:text-yellow-400">{{ $this->thresholds['warning'] }}</span>,
                    <span class="font-medium text-orange-700 dark:text-orange-400">{{ $this->thresholds['critical'] }}</span>, and
                    <span class="font-medium text-red-700 dark:text-red-400">{{ $this->thresholds['urgent'] }}</span> credits
                </flux:text>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Balance History Chart --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 border border-zinc-200 dark:border-zinc-700">
            <flux:heading size="lg" class="mb-4">Credit Balance History</flux:heading>
            <div class="h-64">
                @if($historyError)
                    <div class="flex flex-col items-center justify-center h-full text-zinc-500">
                        <flux:icon.exclamation-triangle class="size-8 mb-2 text-red-500" />
                        <p>{{ $historyError }}</p>
                        <flux:button wire:click="refreshBalance" size="sm" variant="ghost" class="mt-2">
                            Retry
                        </flux:button>
                    </div>
                @elseif(empty($this->balanceHistory['labels']) || empty($this->balanceHistory['data']))
                    <div class="flex items-center justify-center h-full text-zinc-500">
                        No balance history data available yet
                    </div>
                @else
                    <div
                        x-data="balanceHistoryChart(@js($this->balanceHistory))"
                        x-init="initChart()"
                        class="h-full"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Daily Usage Chart --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 border border-zinc-200 dark:border-zinc-700">
            <flux:heading size="lg" class="mb-4">Daily Credit Usage</flux:heading>
            <div class="h-64">
                @if($usageError)
                    <div class="flex flex-col items-center justify-center h-full text-zinc-500">
                        <flux:icon.exclamation-triangle class="size-8 mb-2 text-red-500" />
                        <p>{{ $usageError }}</p>
                        <flux:button wire:click="refreshBalance" size="sm" variant="ghost" class="mt-2">
                            Retry
                        </flux:button>
                    </div>
                @elseif(empty($this->dailyUsage['labels']) || empty($this->dailyUsage['data']))
                    <div class="flex items-center justify-center h-full text-zinc-500">
                        No usage data available yet
                    </div>
                @else
                    <div
                        x-data="dailyUsageChart(@js($this->dailyUsage))"
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

    {{-- Transaction History Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow border border-zinc-200 dark:border-zinc-700">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg" class="flex-shrink-0">Transaction History</flux:heading>
                <div class="flex items-center gap-2">
                    <flux:select wire:model.live="filterType" size="sm" class="min-w-[180px]">
                        <option value="all">All Types</option>
                        <option value="debit">Debits Only</option>
                        <option value="credit">Credits Only</option>
                        <option value="snapshot">Snapshots Only</option>
                        <option value="adjustment">Adjustments Only</option>
                    </flux:select>
                </div>
            </div>
        </div>

        @if($logs->isEmpty())
            <div class="p-12 text-center">
                <flux:icon.document-text class="size-12 mx-auto text-zinc-400 dark:text-zinc-600 mb-4" />
                <flux:heading size="lg" class="mb-2">No Transaction History</flux:heading>
                <flux:text variant="muted">
                    No credit log entries found. Click "Refresh" to log your first balance snapshot.
                </flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Previous Balance</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Change</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">New Balance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($logs as $log)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $log->created_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $typeColors = [
                                            'debit' => 'red',
                                            'credit' => 'green',
                                            'snapshot' => 'zinc',
                                            'adjustment' => 'blue',
                                        ];
                                    @endphp
                                    <flux:badge size="sm" :color="$typeColors[$log->change_type] ?? 'zinc'">
                                        {{ ucfirst($log->change_type) }}
                                    </flux:badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-zinc-900 dark:text-zinc-100">
                                    {{ $log->previous_balance ? number_format($log->previous_balance, 0) : '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">
                                    @if($log->change_amount)
                                        <span class="{{ $log->isDebit() ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                            {{ $log->isDebit() ? '-' : '+' }}{{ number_format($log->change_amount, 0) }}
                                        </span>
                                    @else
                                        <span class="text-zinc-500 dark:text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ number_format($log->balance, 0) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $log->reason ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="p-6 border-t border-zinc-200 dark:border-zinc-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

@assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
@endassets

@script
<script>
    // Balance History Chart
    Alpine.data('balanceHistoryChart', (initialData) => ({
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
                        label: 'Credit Balance',
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
                            ticks: {
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

    // Daily Usage Chart
    Alpine.data('dailyUsageChart', (initialData) => ({
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
                        label: 'Credits Used',
                        data: this.data.data,
                        backgroundColor: '#ef4444',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
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
</script>
@endscript

<div class="w-full" @if($autoRefresh) wire:poll.60s="refreshData" @endif>
    {{-- Page Header --}}
    <div class="mb-8 flex items-start justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Cohort Analysis</flux:heading>
            <flux:text variant="muted" class="mt-2">
                Analyze subscription retention rates and churn patterns by plan type
            </flux:text>
        </div>

        {{-- Controls --}}
        <div class="flex items-center gap-3">
            {{-- Plan Filter Dropdown --}}
            <flux:dropdown>
                <flux:button variant="ghost" icon:trailing="chevron-down" size="sm">
                    @if($selectedPlanId)
                        {{ $this->availablePlans->firstWhere('id', $selectedPlanId)?->name ?? 'Unknown Plan' }}
                    @else
                        All Plans
                    @endif
                </flux:button>
                <flux:menu>
                    <flux:menu.item wire:click="setSelectedPlan(null)">
                        All Plans
                    </flux:menu.item>
                    <flux:menu.separator />
                    @foreach($this->availablePlans as $plan)
                        <flux:menu.item wire:click="setSelectedPlan('{{ $plan->id }}')">
                            {{ $plan->name }}
                        </flux:menu.item>
                    @endforeach
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
    </div>

    {{-- Summary Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Average Retention Rate --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" class="text-sm">Avg 3-Month Retention</flux:text>
                <flux:icon.chart-bar class="size-5 text-zinc-400" />
            </div>
            <flux:heading size="xl" class="font-bold">
                {{ $this->planSummary['average_retention'] ?? 0 }}%
            </flux:heading>
            <flux:text variant="muted" class="text-xs mt-1">
                Across all plans
            </flux:text>
        </div>

        {{-- Best Performing Plan --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" class="text-sm">Best Performing Plan</flux:text>
                <flux:icon.trophy class="size-5 text-amber-500" />
            </div>
            @if($this->planSummary['best_plan'])
                <flux:heading size="xl" class="font-bold truncate">
                    {{ $this->planSummary['best_plan']['plan_name'] }}
                </flux:heading>
                <flux:text variant="muted" class="text-xs mt-1">
                    {{ $this->planSummary['best_plan']['retention_rate'] }}% retention
                </flux:text>
            @else
                <flux:heading size="xl" class="font-bold text-zinc-400">
                    N/A
                </flux:heading>
                <flux:text variant="muted" class="text-xs mt-1">
                    No data available
                </flux:text>
            @endif
        </div>

        {{-- Average Time to Churn --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" class="text-sm">Avg Time to Churn</flux:text>
                <flux:icon.clock class="size-5 text-zinc-400" />
            </div>
            <flux:heading size="xl" class="font-bold">
                {{ $this->planSummary['avg_churn_months'] ?? 0 }}
            </flux:heading>
            <flux:text variant="muted" class="text-xs mt-1">
                Months until cancellation
            </flux:text>
        </div>

        {{-- Total Cohorts --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="muted" class="text-sm">Active Cohorts</flux:text>
                <flux:icon.users class="size-5 text-zinc-400" />
            </div>
            <flux:heading size="xl" class="font-bold">
                {{ $this->planSummary['total_cohorts'] ?? 0 }}
            </flux:heading>
            <flux:text variant="muted" class="text-xs mt-1">
                Last 12 months
            </flux:text>
        </div>
    </div>

    {{-- Cohort Retention Matrix --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <flux:heading size="lg" class="mb-4">Cohort Retention Matrix</flux:heading>
        <flux:text variant="muted" size="sm" class="mb-4">
            Percentage of users retained after each month, grouped by signup cohort and plan
        </flux:text>

        @if(empty($this->cohortMatrix['cohorts']))
            <div class="flex items-center justify-center h-48 text-zinc-500">
                No cohort data available yet. Subscriptions will appear here once created.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">Cohort</th>
                            <th class="text-left py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">Plan</th>
                            <th class="text-center py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">Size</th>
                            @for($i = 1; $i <= $retentionMonths; $i++)
                                <th class="text-center py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">M{{ $i }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->cohortMatrix['cohorts'] as $cohort)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="py-3 px-4 font-medium">{{ $cohort['cohort_label'] }}</td>
                                <td class="py-3 px-4">
                                    <flux:badge size="sm" variant="outline">{{ $cohort['plan_name'] }}</flux:badge>
                                </td>
                                <td class="py-3 px-4 text-center">{{ $cohort['cohort_size'] }}</td>
                                @for($i = 1; $i <= $retentionMonths; $i++)
                                    <td class="py-3 px-4 text-center">
                                        @php $value = $cohort['retention'][$i] ?? null; @endphp
                                        <span class="inline-block px-2 py-1 rounded text-xs font-medium {{ $this->getRetentionColorClass($value) }}">
                                            {{ $value !== null ? $value . '%' : '-' }}
                                        </span>
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Legend --}}
            <div class="mt-4 flex items-center gap-4 text-xs">
                <span class="text-zinc-500 dark:text-zinc-400">Retention:</span>
                <span class="inline-flex items-center gap-1">
                    <span class="w-3 h-3 rounded bg-emerald-100 dark:bg-emerald-900/30"></span>
                    <span>80%+</span>
                </span>
                <span class="inline-flex items-center gap-1">
                    <span class="w-3 h-3 rounded bg-green-100 dark:bg-green-900/30"></span>
                    <span>60-79%</span>
                </span>
                <span class="inline-flex items-center gap-1">
                    <span class="w-3 h-3 rounded bg-yellow-100 dark:bg-yellow-900/30"></span>
                    <span>40-59%</span>
                </span>
                <span class="inline-flex items-center gap-1">
                    <span class="w-3 h-3 rounded bg-orange-100 dark:bg-orange-900/30"></span>
                    <span>20-39%</span>
                </span>
                <span class="inline-flex items-center gap-1">
                    <span class="w-3 h-3 rounded bg-red-100 dark:bg-red-900/30"></span>
                    <span>&lt;20%</span>
                </span>
            </div>
        @endif
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Retention by Plan Chart --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-4">Retention by Plan</flux:heading>
            <flux:text variant="muted" size="sm" class="mb-4">
                Average retention rate over time for each plan
            </flux:text>
            <div class="h-64">
                @if(empty($this->retentionByPlan['datasets']))
                    <div class="flex items-center justify-center h-full text-zinc-500">
                        No retention data available yet
                    </div>
                @else
                    <div
                        x-data="retentionByPlanChart(@js($this->retentionByPlan))"
                        x-init="initChart()"
                        class="h-full"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Churn Timeline Chart --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-4">Churn Timeline</flux:heading>
            <flux:text variant="muted" size="sm" class="mb-4">
                When users typically cancel their subscriptions
            </flux:text>
            <div class="h-64">
                @if(empty($this->churnAnalysis['datasets']))
                    <div class="flex items-center justify-center h-full text-zinc-500">
                        No churn data available yet
                    </div>
                @else
                    <div
                        x-data="churnTimelineChart(@js($this->churnAnalysis))"
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
            Alpine.data('retentionByPlanChart', (initialData) => ({
                chart: null,
                data: initialData,
                colors: ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#ec4899'],

                initChart() {
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.initChart(), 50);
                        return;
                    }

                    const ctx = this.$refs.canvas.getContext('2d');
                    const isDark = document.documentElement.classList.contains('dark');

                    const datasets = this.data.datasets.map((dataset, index) => ({
                        label: dataset.label,
                        data: dataset.data,
                        borderColor: this.colors[index % this.colors.length],
                        backgroundColor: this.colors[index % this.colors.length] + '20',
                        fill: false,
                        tension: 0.4
                    }));

                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: this.data.labels,
                            datasets: datasets
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
                                legend: {
                                    display: true,
                                    position: 'bottom',
                                    labels: {
                                        color: isDark ? '#a1a1aa' : '#71717a',
                                        usePointStyle: true,
                                        padding: 20
                                    }
                                }
                            }
                        }
                    });
                }
            }));

            Alpine.data('churnTimelineChart', (initialData) => ({
                chart: null,
                data: initialData,
                colors: ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#ec4899'],

                initChart() {
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.initChart(), 50);
                        return;
                    }

                    const ctx = this.$refs.canvas.getContext('2d');
                    const isDark = document.documentElement.classList.contains('dark');

                    const datasets = this.data.datasets.map((dataset, index) => ({
                        label: dataset.label,
                        data: dataset.data,
                        backgroundColor: this.colors[index % this.colors.length],
                        borderColor: this.colors[index % this.colors.length],
                        borderWidth: 1
                    }));

                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: this.data.labels,
                            datasets: datasets
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
                                legend: {
                                    display: true,
                                    position: 'bottom',
                                    labels: {
                                        color: isDark ? '#a1a1aa' : '#71717a',
                                        usePointStyle: true,
                                        padding: 20
                                    }
                                }
                            }
                        }
                    });
                }
            }));
        </script>
    @endscript
</div>

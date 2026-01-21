<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\ProvisioningStatus;
use App\Models\Order;
use App\Models\ProvisioningLog;
use App\Models\Subscription;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Cache duration in seconds (5 minutes for analytics data)
     */
    private const CACHE_DURATION = 300;

    /**
     * Get start and end dates from days or custom range
     *
     * @return array{start: Carbon, end: Carbon, cacheKey: string}
     */
    private function getDateRange(?int $days = null, ?string $startDate = null, ?string $endDate = null): array
    {
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $cacheKey = "custom.{$start->format('Y-m-d')}.{$end->format('Y-m-d')}";
        } else {
            $days = $days ?? 30;
            $start = now()->subDays($days - 1)->startOfDay();
            $end = now()->endOfDay();
            $cacheKey = "{$days}d";
        }

        return ['start' => $start, 'end' => $end, 'cacheKey' => $cacheKey];
    }

    /**
     * Get success rate data for the specified date range
     *
     * @return array{labels: array<string>, data: array<float>}
     */
    public function getSuccessRateTimeSeries(?int $days = 30, ?string $startDate = null, ?string $endDate = null): array
    {
        $range = $this->getDateRange($days, $startDate, $endDate);

        return Cache::remember(
            "admin.analytics.success_rate.{$range['cacheKey']}",
            self::CACHE_DURATION,
            function () use ($range): array {
                $period = CarbonPeriod::create($range['start'], '1 day', $range['end']);

                $labels = [];
                $data = [];

                foreach ($period as $date) {
                    $dayStart = $date->copy()->startOfDay();
                    $dayEnd = $date->copy()->endOfDay();

                    $total = ProvisioningLog::whereBetween('created_at', [$dayStart, $dayEnd])->count();

                    if ($total === 0) {
                        $successRate = 0;
                    } else {
                        $successful = ProvisioningLog::whereBetween('created_at', [$dayStart, $dayEnd])
                            ->where('status', ProvisioningStatus::Success)
                            ->count();

                        $successRate = round(($successful / $total) * 100, 1);
                    }

                    $labels[] = $date->format('M d');
                    $data[] = $successRate;
                }

                return [
                    'labels' => $labels,
                    'data' => $data,
                ];
            },
        );
    }

    /**
     * Get order status distribution
     *
     * @return array{labels: array<string>, data: array<int>}
     */
    public function getOrderStatusDistribution(): array
    {
        return Cache::remember(
            'admin.analytics.order_status_distribution',
            self::CACHE_DURATION,
            function (): array {
                $distribution = Order::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->get();

                $labels = [];
                $data = [];

                foreach ($distribution as $item) {
                    $labels[] = $item->status->label();
                    $data[] = $item->count;
                }

                return [
                    'labels' => $labels,
                    'data' => $data,
                ];
            },
        );
    }

    /**
     * Get error type frequency
     *
     * @return array{labels: array<string>, data: array<int>}
     */
    public function getErrorTypeFrequency(?int $days = 30, ?string $startDate = null, ?string $endDate = null): array
    {
        $range = $this->getDateRange($days, $startDate, $endDate);

        return Cache::remember(
            "admin.analytics.error_frequency.{$range['cacheKey']}",
            self::CACHE_DURATION,
            function () use ($range): array {
                $errors = ProvisioningLog::where('status', ProvisioningStatus::Failed)
                    ->whereBetween('created_at', [$range['start'], $range['end']])
                    ->get();

                // Group errors by type/message
                $errorGroups = $errors->groupBy(function ($log) {
                    // Extract error type from error_message or my8k_response
                    if ($log->error_message) {
                        $message = $log->error_message;
                    } elseif ($log->my8k_response && is_array($log->my8k_response)) {
                        $message = $log->my8k_response['error'] ?? $log->my8k_response['message'] ?? 'Unknown Error';
                    } else {
                        $message = 'Unknown Error';
                    }

                    // Truncate long messages
                    return mb_strlen($message) > 50 ? mb_substr($message, 0, 50) . '...' : $message;
                });

                // Get top 10 error types
                $topErrors = $errorGroups->map(fn($group) => $group->count())
                    ->sortDesc()
                    ->take(10);

                return [
                    'labels' => $topErrors->keys()->toArray(),
                    'data' => $topErrors->values()->toArray(),
                ];
            },
        );
    }

    /**
     * Get provisioning performance metrics
     *
     * @return array{
     *     avgDuration: float,
     *     totalProvisioned: int,
     *     successRate: float,
     *     failureRate: float,
     *     pendingCount: int
     * }
     */
    public function getProvisioningPerformance(?int $days = 30, ?string $startDate = null, ?string $endDate = null): array
    {
        $range = $this->getDateRange($days, $startDate, $endDate);

        return Cache::remember(
            "admin.analytics.provisioning_performance.{$range['cacheKey']}",
            self::CACHE_DURATION,
            function () use ($range): array {
                $logs = ProvisioningLog::whereBetween('created_at', [$range['start'], $range['end']])->get();

                $total = $logs->count();

                if ($total === 0) {
                    return [
                        'avgDuration' => 0,
                        'totalProvisioned' => 0,
                        'successRate' => 0,
                        'failureRate' => 0,
                        'pendingCount' => 0,
                    ];
                }

                $successful = $logs->where('status', ProvisioningStatus::Success)->count();
                $failed = $logs->where('status', ProvisioningStatus::Failed)->count();
                $pending = $logs->where('status', ProvisioningStatus::Pending)->count();

                // Calculate average duration for completed logs
                $completedLogs = $logs->whereIn('status', [ProvisioningStatus::Success, ProvisioningStatus::Failed])
                    ->filter(fn($log) => $log->created_at && $log->updated_at);

                $avgDuration = 0;
                if ($completedLogs->isNotEmpty()) {
                    $totalDuration = $completedLogs->sum(
                        fn($log)
                        => $log->created_at->diffInSeconds($log->updated_at),
                    );
                    $avgDuration = round($totalDuration / $completedLogs->count(), 2);
                }

                return [
                    'avgDuration' => $avgDuration,
                    'totalProvisioned' => $total,
                    'successRate' => round(($successful / $total) * 100, 1),
                    'failureRate' => round(($failed / $total) * 100, 1),
                    'pendingCount' => $pending,
                ];
            },
        );
    }

    /**
     * Get subscription growth over time
     *
     * @return array{labels: array<string>, data: array<int>}
     */
    public function getSubscriptionGrowth(?int $days = 30, ?string $startDate = null, ?string $endDate = null): array
    {
        $range = $this->getDateRange($days, $startDate, $endDate);

        return Cache::remember(
            "admin.analytics.subscription_growth.{$range['cacheKey']}",
            self::CACHE_DURATION,
            function () use ($range): array {
                $period = CarbonPeriod::create($range['start'], '1 day', $range['end']);

                $labels = [];
                $data = [];

                foreach ($period as $date) {
                    $dayEnd = $date->copy()->endOfDay();

                    // Get cumulative count of subscriptions created up to this date
                    $count = Subscription::where('created_at', '<=', $dayEnd)->count();

                    $labels[] = $date->format('M d');
                    $data[] = $count;
                }

                return [
                    'labels' => $labels,
                    'data' => $data,
                ];
            },
        );
    }

    /**
     * Get revenue over time
     *
     * @return array{labels: array<string>, data: array<float>}
     */
    public function getRevenueTimeSeries(?int $days = 30, ?string $startDate = null, ?string $endDate = null): array
    {
        $range = $this->getDateRange($days, $startDate, $endDate);

        return Cache::remember(
            "admin.analytics.revenue.{$range['cacheKey']}",
            self::CACHE_DURATION,
            function () use ($range): array {
                $period = CarbonPeriod::create($range['start'], '1 day', $range['end']);

                $labels = [];
                $data = [];

                foreach ($period as $date) {
                    $dayStart = $date->copy()->startOfDay();
                    $dayEnd = $date->copy()->endOfDay();

                    $revenue = Order::whereBetween('created_at', [$dayStart, $dayEnd])
                        ->where('status', OrderStatus::Provisioned)
                        ->sum('amount');

                    $labels[] = $date->format('M d');
                    $data[] = round($revenue, 2);
                }

                return [
                    'labels' => $labels,
                    'data' => $data,
                ];
            },
        );
    }

    /**
     * Export analytics data to CSV format
     *
     * @return array{filename: string, content: string}
     */
    public function exportToCsv(?int $days = 30, ?string $startDate = null, ?string $endDate = null): array
    {
        $range = $this->getDateRange($days, $startDate, $endDate);

        $successRate = $this->getSuccessRateTimeSeries($days, $startDate, $endDate);
        $revenue = $this->getRevenueTimeSeries($days, $startDate, $endDate);
        $subscriptionGrowth = $this->getSubscriptionGrowth($days, $startDate, $endDate);
        $performance = $this->getProvisioningPerformance($days, $startDate, $endDate);
        $errors = $this->getErrorTypeFrequency($days, $startDate, $endDate);
        $orderStatus = $this->getOrderStatusDistribution();

        $output = fopen('php://temp', 'r+');

        // Summary section
        fputcsv($output, ['Analytics Report']);
        fputcsv($output, ['Date Range', $range['start']->format('Y-m-d') . ' to ' . $range['end']->format('Y-m-d')]);
        fputcsv($output, ['Generated', now()->toDateTimeString()]);
        fputcsv($output, []);

        // Performance metrics
        fputcsv($output, ['Performance Metrics']);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Provisioned', $performance['totalProvisioned']]);
        fputcsv($output, ['Success Rate', $performance['successRate'] . '%']);
        fputcsv($output, ['Failure Rate', $performance['failureRate'] . '%']);
        fputcsv($output, ['Avg Duration (seconds)', $performance['avgDuration']]);
        fputcsv($output, ['Pending Count', $performance['pendingCount']]);
        fputcsv($output, []);

        // Daily success rate
        fputcsv($output, ['Daily Success Rate']);
        fputcsv($output, ['Date', 'Success Rate (%)']);
        foreach ($successRate['labels'] as $i => $label) {
            fputcsv($output, [$label, $successRate['data'][$i]]);
        }
        fputcsv($output, []);

        // Daily revenue
        fputcsv($output, ['Daily Revenue']);
        fputcsv($output, ['Date', 'Revenue']);
        foreach ($revenue['labels'] as $i => $label) {
            fputcsv($output, [$label, $revenue['data'][$i]]);
        }
        fputcsv($output, []);

        // Subscription growth
        fputcsv($output, ['Subscription Growth']);
        fputcsv($output, ['Date', 'Total Subscriptions']);
        foreach ($subscriptionGrowth['labels'] as $i => $label) {
            fputcsv($output, [$label, $subscriptionGrowth['data'][$i]]);
        }
        fputcsv($output, []);

        // Order status distribution
        fputcsv($output, ['Order Status Distribution']);
        fputcsv($output, ['Status', 'Count']);
        foreach ($orderStatus['labels'] as $i => $label) {
            fputcsv($output, [$label, $orderStatus['data'][$i]]);
        }
        fputcsv($output, []);

        // Error frequency
        fputcsv($output, ['Top Error Types']);
        fputcsv($output, ['Error', 'Count']);
        foreach ($errors['labels'] as $i => $label) {
            fputcsv($output, [$label, $errors['data'][$i]]);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        $filename = 'analytics_' . $range['start']->format('Y-m-d') . '_to_' . $range['end']->format('Y-m-d') . '.csv';

        return [
            'filename' => $filename,
            'content' => $content,
        ];
    }

    /**
     * Clear all analytics caches
     */
    public function clearCache(): void
    {
        $patterns = [
            'admin.analytics.success_rate.*',
            'admin.analytics.order_status_distribution',
            'admin.analytics.error_frequency.*',
            'admin.analytics.provisioning_performance.*',
            'admin.analytics.subscription_growth.*',
            'admin.analytics.revenue.*',
        ];

        foreach ($patterns as $pattern) {
            // Since we can't use wildcards with Cache::forget, we'll clear specific known keys
            for ($days = 1; $days <= 90; $days++) {
                Cache::forget(str_replace('*', "{$days}d", $pattern));
            }
        }

        Cache::forget('admin.analytics.order_status_distribution');
    }
}

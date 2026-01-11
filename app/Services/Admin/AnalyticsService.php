<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\ProvisioningStatus;
use App\Models\Order;
use App\Models\ProvisioningLog;
use App\Models\Subscription;
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
     * Get success rate data for the last N days
     *
     * @return array{labels: array<string>, data: array<float>}
     */
    public function getSuccessRateTimeSeries(int $days = 30): array
    {
        return Cache::remember(
            "admin.analytics.success_rate.{$days}d",
            self::CACHE_DURATION,
            function () use ($days): array {
                $startDate = now()->subDays($days - 1)->startOfDay();
                $endDate = now()->endOfDay();

                $period = CarbonPeriod::create($startDate, '1 day', $endDate);

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
    public function getErrorTypeFrequency(int $days = 30): array
    {
        return Cache::remember(
            "admin.analytics.error_frequency.{$days}d",
            self::CACHE_DURATION,
            function () use ($days): array {
                $startDate = now()->subDays($days)->startOfDay();

                $errors = ProvisioningLog::where('status', ProvisioningStatus::Failed)
                    ->where('created_at', '>=', $startDate)
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
    public function getProvisioningPerformance(int $days = 30): array
    {
        return Cache::remember(
            "admin.analytics.provisioning_performance.{$days}d",
            self::CACHE_DURATION,
            function () use ($days): array {
                $startDate = now()->subDays($days)->startOfDay();

                $logs = ProvisioningLog::where('created_at', '>=', $startDate)->get();

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
    public function getSubscriptionGrowth(int $days = 30): array
    {
        return Cache::remember(
            "admin.analytics.subscription_growth.{$days}d",
            self::CACHE_DURATION,
            function () use ($days): array {
                $startDate = now()->subDays($days - 1)->startOfDay();
                $endDate = now()->endOfDay();

                $period = CarbonPeriod::create($startDate, '1 day', $endDate);

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
    public function getRevenueTimeSeries(int $days = 30): array
    {
        return Cache::remember(
            "admin.analytics.revenue.{$days}d",
            self::CACHE_DURATION,
            function () use ($days): array {
                $startDate = now()->subDays($days - 1)->startOfDay();
                $endDate = now()->endOfDay();

                $period = CarbonPeriod::create($startDate, '1 day', $endDate);

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

<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\ProvisioningLog;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    /**
     * Cache duration in seconds (60 seconds to match dashboard polling)
     */
    private const CACHE_DURATION = 60;

    /**
     * Get count of active subscriptions
     */
    public function getActiveSubscriptionsCount(): int
    {
        return Cache::remember(
            'admin.stats.active_subscriptions',
            self::CACHE_DURATION,
            fn() => Subscription::active()->count(),
        );
    }

    /**
     * Get count of orders created today
     */
    public function getOrdersTodayCount(): int
    {
        return Cache::remember(
            'admin.stats.orders_today',
            self::CACHE_DURATION,
            fn() => Order::whereDate('created_at', today())->count(),
        );
    }

    /**
     * Get provisioning success rate from last 24 hours
     *
     * @return float Success rate as percentage (0-100)
     */
    public function getProvisioningSuccessRate(): float
    {
        return Cache::remember(
            'admin.stats.provisioning_success_rate',
            self::CACHE_DURATION,
            function (): float {
                $total = ProvisioningLog::recent(24)->count();

                if ($total === 0) {
                    return 100.0; // Default to 100% if no recent logs
                }

                $successful = ProvisioningLog::recent(24)->success()->count();

                return round(($successful / $total) * 100, 1);
            },
        );
    }

    /**
     * Get count of failed jobs
     */
    public function getFailedJobsCount(): int
    {
        return Cache::remember(
            'admin.stats.failed_jobs',
            self::CACHE_DURATION,
            fn() => DB::table('failed_jobs')->count(),
        );
    }

    /**
     * Clear all cached statistics
     */
    public function clearCache(): void
    {
        Cache::forget('admin.stats.active_subscriptions');
        Cache::forget('admin.stats.orders_today');
        Cache::forget('admin.stats.provisioning_success_rate');
        Cache::forget('admin.stats.failed_jobs');
    }
}

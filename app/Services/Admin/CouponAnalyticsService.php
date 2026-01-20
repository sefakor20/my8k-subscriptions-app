<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\CouponDiscountType;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CouponAnalyticsService
{
    /**
     * Cache duration in seconds (5 minutes for analytics data)
     */
    private const CACHE_DURATION = 300;

    /**
     * Get summary metrics for coupons
     *
     * @return array{
     *     totalRedemptions: int,
     *     totalDiscountGiven: float,
     *     revenueAfterDiscounts: float,
     *     avgDiscountPerUse: float,
     *     activeCoupons: int
     * }
     */
    public function getMetrics(int $days = 30): array
    {
        return Cache::remember(
            "admin.coupon-analytics.metrics.{$days}d",
            self::CACHE_DURATION,
            function () use ($days): array {
                $startDate = now()->subDays($days)->startOfDay();

                $redemptions = CouponRedemption::where('created_at', '>=', $startDate)->get();

                $totalRedemptions = $redemptions->count();
                $totalDiscountGiven = (float) $redemptions->sum('discount_amount');
                $revenueAfterDiscounts = (float) $redemptions->sum('final_amount');
                $avgDiscountPerUse = $totalRedemptions > 0
                    ? round($totalDiscountGiven / $totalRedemptions, 2)
                    : 0;

                $activeCoupons = Coupon::where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('valid_until')
                            ->orWhere('valid_until', '>=', now());
                    })
                    ->count();

                return [
                    'totalRedemptions' => $totalRedemptions,
                    'totalDiscountGiven' => round($totalDiscountGiven, 2),
                    'revenueAfterDiscounts' => round($revenueAfterDiscounts, 2),
                    'avgDiscountPerUse' => $avgDiscountPerUse,
                    'activeCoupons' => $activeCoupons,
                ];
            },
        );
    }

    /**
     * Get redemptions time series for chart
     *
     * @return array{labels: array<string>, data: array<int>}
     */
    public function getRedemptionsTimeSeries(int $days = 30): array
    {
        return Cache::remember(
            "admin.coupon-analytics.redemptions_time_series.{$days}d",
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

                    $count = CouponRedemption::whereBetween('created_at', [$dayStart, $dayEnd])->count();

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
     * Get discount amount time series for chart
     *
     * @return array{labels: array<string>, data: array<float>}
     */
    public function getDiscountTimeSeries(int $days = 30): array
    {
        return Cache::remember(
            "admin.coupon-analytics.discount_time_series.{$days}d",
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

                    $discount = CouponRedemption::whereBetween('created_at', [$dayStart, $dayEnd])
                        ->sum('discount_amount');

                    $labels[] = $date->format('M d');
                    $data[] = round((float) $discount, 2);
                }

                return [
                    'labels' => $labels,
                    'data' => $data,
                ];
            },
        );
    }

    /**
     * Get top coupons by usage
     *
     * @return array<array{code: string, name: string, redemptions: int, total_discount: float, avg_discount: float}>
     */
    public function getTopCoupons(int $days = 30, int $limit = 10): array
    {
        return Cache::remember(
            "admin.coupon-analytics.top_coupons.{$days}d.{$limit}",
            self::CACHE_DURATION,
            function () use ($days, $limit): array {
                $startDate = now()->subDays($days)->startOfDay();

                $topCoupons = CouponRedemption::select(
                    'coupon_id',
                    DB::raw('COUNT(*) as redemptions'),
                    DB::raw('SUM(discount_amount) as total_discount'),
                    DB::raw('AVG(discount_amount) as avg_discount'),
                )
                    ->where('created_at', '>=', $startDate)
                    ->groupBy('coupon_id')
                    ->orderByDesc('redemptions')
                    ->limit($limit)
                    ->with('coupon:id,code,name')
                    ->get();

                return $topCoupons->map(fn($item) => [
                    'code' => $item->coupon?->code ?? 'Deleted',
                    'name' => $item->coupon?->name ?? 'Deleted Coupon',
                    'redemptions' => (int) $item->redemptions,
                    'total_discount' => round((float) $item->total_discount, 2),
                    'avg_discount' => round((float) $item->avg_discount, 2),
                ])->toArray();
            },
        );
    }

    /**
     * Get discount type distribution
     *
     * @return array{labels: array<string>, data: array<int>}
     */
    public function getDiscountTypeDistribution(int $days = 30): array
    {
        return Cache::remember(
            "admin.coupon-analytics.discount_type_distribution.{$days}d",
            self::CACHE_DURATION,
            function () use ($days): array {
                $startDate = now()->subDays($days)->startOfDay();

                $distribution = CouponRedemption::select(
                    'coupons.discount_type',
                    DB::raw('COUNT(*) as count'),
                )
                    ->join('coupons', 'coupon_redemptions.coupon_id', '=', 'coupons.id')
                    ->where('coupon_redemptions.created_at', '>=', $startDate)
                    ->groupBy('coupons.discount_type')
                    ->get();

                $labels = [];
                $data = [];

                foreach ($distribution as $item) {
                    $labels[] = CouponDiscountType::from($item->discount_type)->label();
                    $data[] = (int) $item->count;
                }

                return [
                    'labels' => $labels,
                    'data' => $data,
                ];
            },
        );
    }

    /**
     * Get currency distribution
     *
     * @return array{labels: array<string>, data: array<float>}
     */
    public function getCurrencyDistribution(int $days = 30): array
    {
        return Cache::remember(
            "admin.coupon-analytics.currency_distribution.{$days}d",
            self::CACHE_DURATION,
            function () use ($days): array {
                $startDate = now()->subDays($days)->startOfDay();

                $distribution = CouponRedemption::select(
                    'currency',
                    DB::raw('SUM(discount_amount) as total_discount'),
                )
                    ->where('created_at', '>=', $startDate)
                    ->groupBy('currency')
                    ->orderByDesc('total_discount')
                    ->get();

                $labels = [];
                $data = [];

                foreach ($distribution as $item) {
                    $labels[] = $item->currency;
                    $data[] = round((float) $item->total_discount, 2);
                }

                return [
                    'labels' => $labels,
                    'data' => $data,
                ];
            },
        );
    }

    /**
     * Clear all coupon analytics caches
     */
    public function clearCache(): void
    {
        $patterns = [
            'admin.coupon-analytics.metrics',
            'admin.coupon-analytics.redemptions_time_series',
            'admin.coupon-analytics.discount_time_series',
            'admin.coupon-analytics.top_coupons',
            'admin.coupon-analytics.discount_type_distribution',
            'admin.coupon-analytics.currency_distribution',
        ];

        foreach ($patterns as $pattern) {
            for ($days = 1; $days <= 90; $days++) {
                Cache::forget("{$pattern}.{$days}d");
                // Also clear top_coupons with limit variations
                for ($limit = 5; $limit <= 20; $limit += 5) {
                    Cache::forget("{$pattern}.{$days}d.{$limit}");
                }
            }
        }
    }
}

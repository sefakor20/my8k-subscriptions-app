<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\CouponDiscountType;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use Carbon\Carbon;
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
    public function getMetrics(?int $days = 30, ?string $startDate = null, ?string $endDate = null): array
    {
        $range = $this->getDateRange($days, $startDate, $endDate);

        return Cache::remember(
            "admin.coupon-analytics.metrics.{$range['cacheKey']}",
            self::CACHE_DURATION,
            function () use ($range): array {
                $redemptions = CouponRedemption::whereBetween('created_at', [$range['start'], $range['end']])->get();

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
    public function getRedemptionsTimeSeries(?int $days = 30, ?string $startDate = null, ?string $endDate = null): array
    {
        $range = $this->getDateRange($days, $startDate, $endDate);

        return Cache::remember(
            "admin.coupon-analytics.redemptions_time_series.{$range['cacheKey']}",
            self::CACHE_DURATION,
            function () use ($range): array {
                $period = CarbonPeriod::create($range['start'], '1 day', $range['end']);

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
    public function getDiscountTimeSeries(?int $days = 30, ?string $startDate = null, ?string $endDate = null): array
    {
        $range = $this->getDateRange($days, $startDate, $endDate);

        return Cache::remember(
            "admin.coupon-analytics.discount_time_series.{$range['cacheKey']}",
            self::CACHE_DURATION,
            function () use ($range): array {
                $period = CarbonPeriod::create($range['start'], '1 day', $range['end']);

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
    public function getTopCoupons(?int $days = 30, ?string $startDate = null, ?string $endDate = null, int $limit = 10): array
    {
        $range = $this->getDateRange($days, $startDate, $endDate);

        return Cache::remember(
            "admin.coupon-analytics.top_coupons.{$range['cacheKey']}.{$limit}",
            self::CACHE_DURATION,
            function () use ($range, $limit): array {
                $topCoupons = CouponRedemption::select(
                    'coupon_id',
                    DB::raw('COUNT(*) as redemptions'),
                    DB::raw('SUM(discount_amount) as total_discount'),
                    DB::raw('AVG(discount_amount) as avg_discount'),
                )
                    ->whereBetween('created_at', [$range['start'], $range['end']])
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
    public function getDiscountTypeDistribution(?int $days = 30, ?string $startDate = null, ?string $endDate = null): array
    {
        $range = $this->getDateRange($days, $startDate, $endDate);

        return Cache::remember(
            "admin.coupon-analytics.discount_type_distribution.{$range['cacheKey']}",
            self::CACHE_DURATION,
            function () use ($range): array {
                $distribution = CouponRedemption::select(
                    'coupons.discount_type',
                    DB::raw('COUNT(*) as count'),
                )
                    ->join('coupons', 'coupon_redemptions.coupon_id', '=', 'coupons.id')
                    ->whereBetween('coupon_redemptions.created_at', [$range['start'], $range['end']])
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
    public function getCurrencyDistribution(?int $days = 30, ?string $startDate = null, ?string $endDate = null): array
    {
        $range = $this->getDateRange($days, $startDate, $endDate);

        return Cache::remember(
            "admin.coupon-analytics.currency_distribution.{$range['cacheKey']}",
            self::CACHE_DURATION,
            function () use ($range): array {
                $distribution = CouponRedemption::select(
                    'currency',
                    DB::raw('SUM(discount_amount) as total_discount'),
                )
                    ->whereBetween('created_at', [$range['start'], $range['end']])
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
     * Export coupon analytics data to CSV format
     *
     * @return array{filename: string, content: string}
     */
    public function exportToCsv(?int $days = 30, ?string $startDate = null, ?string $endDate = null): array
    {
        $range = $this->getDateRange($days, $startDate, $endDate);

        $metrics = $this->getMetrics($days, $startDate, $endDate);
        $redemptions = $this->getRedemptionsTimeSeries($days, $startDate, $endDate);
        $discounts = $this->getDiscountTimeSeries($days, $startDate, $endDate);
        $topCoupons = $this->getTopCoupons($days, $startDate, $endDate);
        $discountTypes = $this->getDiscountTypeDistribution($days, $startDate, $endDate);
        $currencies = $this->getCurrencyDistribution($days, $startDate, $endDate);

        $output = fopen('php://temp', 'r+');

        // Summary section
        fputcsv($output, ['Coupon Analytics Report']);
        fputcsv($output, ['Date Range', $range['start']->format('Y-m-d') . ' to ' . $range['end']->format('Y-m-d')]);
        fputcsv($output, ['Generated', now()->toDateTimeString()]);
        fputcsv($output, []);

        // Summary metrics
        fputcsv($output, ['Summary Metrics']);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Redemptions', $metrics['totalRedemptions']]);
        fputcsv($output, ['Total Discount Given', $metrics['totalDiscountGiven']]);
        fputcsv($output, ['Revenue After Discounts', $metrics['revenueAfterDiscounts']]);
        fputcsv($output, ['Avg Discount Per Use', $metrics['avgDiscountPerUse']]);
        fputcsv($output, ['Active Coupons', $metrics['activeCoupons']]);
        fputcsv($output, []);

        // Daily redemptions
        fputcsv($output, ['Daily Redemptions']);
        fputcsv($output, ['Date', 'Redemptions']);
        foreach ($redemptions['labels'] as $i => $label) {
            fputcsv($output, [$label, $redemptions['data'][$i]]);
        }
        fputcsv($output, []);

        // Daily discounts
        fputcsv($output, ['Daily Discount Amount']);
        fputcsv($output, ['Date', 'Discount Amount']);
        foreach ($discounts['labels'] as $i => $label) {
            fputcsv($output, [$label, $discounts['data'][$i]]);
        }
        fputcsv($output, []);

        // Top coupons
        fputcsv($output, ['Top Performing Coupons']);
        fputcsv($output, ['Code', 'Name', 'Redemptions', 'Total Discount', 'Avg Discount']);
        foreach ($topCoupons as $coupon) {
            fputcsv($output, [
                $coupon['code'],
                $coupon['name'],
                $coupon['redemptions'],
                $coupon['total_discount'],
                $coupon['avg_discount'],
            ]);
        }
        fputcsv($output, []);

        // Discount type distribution
        fputcsv($output, ['Discount Type Distribution']);
        fputcsv($output, ['Type', 'Count']);
        foreach ($discountTypes['labels'] as $i => $label) {
            fputcsv($output, [$label, $discountTypes['data'][$i]]);
        }
        fputcsv($output, []);

        // Currency distribution
        fputcsv($output, ['Currency Distribution']);
        fputcsv($output, ['Currency', 'Total Discount']);
        foreach ($currencies['labels'] as $i => $label) {
            fputcsv($output, [$label, $currencies['data'][$i]]);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        $filename = 'coupon_analytics_' . $range['start']->format('Y-m-d') . '_to_' . $range['end']->format('Y-m-d') . '.csv';

        return [
            'filename' => $filename,
            'content' => $content,
        ];
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

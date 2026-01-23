<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CohortAnalyticsService
{
    /**
     * Cache duration in seconds (5 minutes for analytics data)
     */
    private const CACHE_DURATION = 300;

    /**
     * Get the date format expression for the current database driver
     */
    private function getDateFormatExpression(string $column): string
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')", // MySQL
        };
    }

    /**
     * Get cohort retention matrix showing retention % by month for each cohort
     *
     * @return array{cohorts: array, plans: array}
     */
    public function getCohortRetentionMatrix(?string $planId = null, int $cohortMonths = 12, int $retentionMonths = 6): array
    {
        $cacheKey = "admin.cohort.matrix.{$planId}.{$cohortMonths}.{$retentionMonths}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($planId, $cohortMonths, $retentionMonths): array {
            $startDate = now()->subMonths($cohortMonths)->startOfMonth();
            $endDate = now()->endOfMonth();

            $dateFormat = $this->getDateFormatExpression('subscriptions.created_at');

            $query = Subscription::query()
                ->select([
                    DB::raw("{$dateFormat} as cohort_month"),
                    'plans.id as plan_id',
                    'plans.name as plan_name',
                    DB::raw('COUNT(DISTINCT subscriptions.id) as cohort_size'),
                ])
                ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
                ->where('subscriptions.created_at', '>=', $startDate)
                ->where('subscriptions.created_at', '<=', $endDate)
                ->groupBy('cohort_month', 'plans.id', 'plans.name')
                ->orderBy('cohort_month', 'desc')
                ->orderBy('plans.name');

            if ($planId) {
                $query->where('plans.id', $planId);
            }

            $cohortData = $query->get();

            // Build retention matrix
            $matrix = [];

            foreach ($cohortData as $cohort) {
                $cohortDate = Carbon::createFromFormat('Y-m', $cohort->cohort_month)->startOfMonth();
                $retentionData = $this->calculateRetentionForCohort(
                    $cohort->cohort_month,
                    $cohort->plan_id,
                    $retentionMonths,
                );

                $matrix[] = [
                    'cohort_month' => $cohort->cohort_month,
                    'cohort_label' => $cohortDate->format('M Y'),
                    'plan_id' => $cohort->plan_id,
                    'plan_name' => $cohort->plan_name,
                    'cohort_size' => $cohort->cohort_size,
                    'retention' => $retentionData,
                ];
            }

            // Get unique plan names for legend
            $plans = collect($matrix)->pluck('plan_name')->unique()->values()->toArray();

            return [
                'cohorts' => $matrix,
                'plans' => $plans,
            ];
        });
    }

    /**
     * Calculate retention percentages for a specific cohort
     *
     * @return array<int, float|null>
     */
    private function calculateRetentionForCohort(string $cohortMonth, string $planId, int $retentionMonths): array
    {
        $cohortDate = Carbon::createFromFormat('Y-m', $cohortMonth)->startOfMonth();
        $cohortEndDate = $cohortDate->copy()->endOfMonth();

        // Get all subscriptions in this cohort
        $dateFormat = $this->getDateFormatExpression('created_at');
        $cohortSubscriptions = Subscription::query()
            ->where('plan_id', $planId)
            ->whereRaw("{$dateFormat} = ?", [$cohortMonth])
            ->get();

        $cohortSize = $cohortSubscriptions->count();

        if ($cohortSize === 0) {
            return array_fill(1, $retentionMonths, null);
        }

        $retention = [];

        for ($month = 1; $month <= $retentionMonths; $month++) {
            $checkDate = $cohortDate->copy()->addMonths($month);

            // Don't calculate retention for future months
            if ($checkDate->isAfter(now())) {
                $retention[$month] = null;

                continue;
            }

            // Count subscriptions that were still active at the end of this month
            $activeCount = $cohortSubscriptions->filter(function ($subscription) use ($checkDate) {
                // Subscription is retained if:
                // 1. It was not cancelled before this month, OR
                // 2. It was still active (expires_at is after this month)
                $checkEndOfMonth = $checkDate->copy()->endOfMonth();

                // If cancelled before this check date, not retained
                if ($subscription->cancelled_at && Carbon::parse($subscription->cancelled_at)->isBefore($checkDate->startOfMonth())) {
                    return false;
                }

                // If status is active or the expiry date is after the check month
                if ($subscription->status === SubscriptionStatus::Active) {
                    return true;
                }

                // If expired before the check month, not retained
                if ($subscription->expires_at && Carbon::parse($subscription->expires_at)->isBefore($checkDate->startOfMonth())) {
                    return false;
                }

                return true;
            })->count();

            $retention[$month] = round(($activeCount / $cohortSize) * 100, 1);
        }

        return $retention;
    }

    /**
     * Get retention rate comparison by plan
     *
     * @return array{labels: array, datasets: array}
     */
    public function getRetentionByPlan(int $retentionMonths = 6): array
    {
        $cacheKey = "admin.cohort.retention_by_plan.{$retentionMonths}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($retentionMonths): array {
            $plans = Plan::where('is_active', true)->get();

            $labels = [];
            for ($i = 1; $i <= $retentionMonths; $i++) {
                $labels[] = "Month {$i}";
            }

            $datasets = [];

            foreach ($plans as $plan) {
                // Get average retention for this plan across all cohorts
                $avgRetention = $this->getAverageRetentionForPlan($plan->id, $retentionMonths);

                $datasets[] = [
                    'label' => $plan->name,
                    'data' => $avgRetention,
                    'plan_id' => $plan->id,
                ];
            }

            return [
                'labels' => $labels,
                'datasets' => $datasets,
            ];
        });
    }

    /**
     * Get average retention rates for a plan across all cohorts
     *
     * @return array<float>
     */
    private function getAverageRetentionForPlan(string $planId, int $retentionMonths): array
    {
        $startDate = now()->subMonths(6)->startOfMonth();
        $dateFormat = $this->getDateFormatExpression('created_at');

        $cohorts = Subscription::query()
            ->select(DB::raw("{$dateFormat} as cohort_month"))
            ->where('plan_id', $planId)
            ->where('created_at', '>=', $startDate)
            ->groupBy('cohort_month')
            ->pluck('cohort_month');

        $allRetention = [];

        foreach ($cohorts as $cohortMonth) {
            $retention = $this->calculateRetentionForCohort($cohortMonth, $planId, $retentionMonths);

            foreach ($retention as $month => $rate) {
                if ($rate !== null) {
                    $allRetention[$month][] = $rate;
                }
            }
        }

        // Calculate averages
        $avgRetention = [];
        for ($i = 1; $i <= $retentionMonths; $i++) {
            if (isset($allRetention[$i]) && count($allRetention[$i]) > 0) {
                $avgRetention[] = round(array_sum($allRetention[$i]) / count($allRetention[$i]), 1);
            } else {
                $avgRetention[] = 0;
            }
        }

        return $avgRetention;
    }

    /**
     * Get churn analysis showing when users typically cancel, by plan
     *
     * @return array{labels: array, datasets: array}
     */
    public function getChurnAnalysisByPlan(): array
    {
        return Cache::remember('admin.cohort.churn_analysis', self::CACHE_DURATION, function (): array {
            $plans = Plan::where('is_active', true)->get();

            // Labels for months (0-12 months after subscription)
            $labels = ['Month 1', 'Month 2', 'Month 3', 'Month 4', 'Month 5', 'Month 6', 'Month 7+'];

            $datasets = [];

            foreach ($plans as $plan) {
                $churnData = $this->getChurnDistributionForPlan($plan->id);

                $datasets[] = [
                    'label' => $plan->name,
                    'data' => $churnData,
                    'plan_id' => $plan->id,
                ];
            }

            return [
                'labels' => $labels,
                'datasets' => $datasets,
            ];
        });
    }

    /**
     * Get churn distribution for a specific plan
     *
     * @return array<int>
     */
    private function getChurnDistributionForPlan(string $planId): array
    {
        // Get cancelled subscriptions with the time between creation and cancellation
        $cancelledSubs = Subscription::query()
            ->where('plan_id', $planId)
            ->whereNotNull('cancelled_at')
            ->select([
                'created_at',
                'cancelled_at',
            ])
            ->get();

        $distribution = array_fill(0, 7, 0); // 7 buckets: months 1-6, and 7+

        foreach ($cancelledSubs as $sub) {
            $createdAt = Carbon::parse($sub->created_at);
            $cancelledAt = Carbon::parse($sub->cancelled_at);
            $monthsUntilChurn = (int) $createdAt->diffInMonths($cancelledAt);

            if ($monthsUntilChurn < 6) {
                $distribution[$monthsUntilChurn]++;
            } else {
                $distribution[6]++; // 7+ months bucket
            }
        }

        return $distribution;
    }

    /**
     * Get summary metrics for plan retention
     *
     * @return array{average_retention: float, best_plan: array|null, avg_churn_months: float, total_cohorts: int}
     */
    public function getPlanRetentionSummary(): array
    {
        return Cache::remember('admin.cohort.plan_summary', self::CACHE_DURATION, function (): array {
            $plans = Plan::where('is_active', true)->get();

            $planMetrics = [];
            $totalRetention = 0;
            $planCount = 0;

            foreach ($plans as $plan) {
                $avgRetention = $this->getAverageRetentionForPlan($plan->id, 3);
                $threeMonthRetention = ! empty($avgRetention) && isset($avgRetention[2]) ? $avgRetention[2] : 0;

                $planMetrics[] = [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'retention_rate' => $threeMonthRetention,
                ];

                $totalRetention += $threeMonthRetention;
                $planCount++;
            }

            // Find best performing plan
            $bestPlan = collect($planMetrics)->sortByDesc('retention_rate')->first();

            // Calculate average time to churn
            $avgChurnMonths = $this->calculateAverageChurnTime();

            // Count total cohorts
            $dateFormat = $this->getDateFormatExpression('created_at');
            $totalCohorts = Subscription::query()
                ->select(DB::raw("{$dateFormat} as cohort_month"))
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy(DB::raw($dateFormat))
                ->get()
                ->count();

            return [
                'average_retention' => $planCount > 0 ? round($totalRetention / $planCount, 1) : 0,
                'best_plan' => $bestPlan,
                'avg_churn_months' => $avgChurnMonths,
                'total_cohorts' => $totalCohorts,
            ];
        });
    }

    /**
     * Calculate average time to churn in months
     */
    private function calculateAverageChurnTime(): float
    {
        $cancelledSubs = Subscription::query()
            ->whereNotNull('cancelled_at')
            ->select(['created_at', 'cancelled_at'])
            ->get();

        if ($cancelledSubs->isEmpty()) {
            return 0;
        }

        $totalMonths = 0;

        foreach ($cancelledSubs as $sub) {
            $createdAt = Carbon::parse($sub->created_at);
            $cancelledAt = Carbon::parse($sub->cancelled_at);
            $totalMonths += $createdAt->diffInMonths($cancelledAt);
        }

        return round($totalMonths / $cancelledSubs->count(), 1);
    }

    /**
     * Get list of available plans for filtering
     */
    public function getAvailablePlans(): Collection
    {
        return Plan::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Export cohort data to CSV
     *
     * @return array{content: string, filename: string}
     */
    public function exportToCsv(?string $planId = null): array
    {
        $matrix = $this->getCohortRetentionMatrix($planId, 12, 6);

        $lines = [];

        // Header row
        $headers = ['Cohort', 'Plan', 'Cohort Size', 'Month 1', 'Month 2', 'Month 3', 'Month 4', 'Month 5', 'Month 6'];
        $lines[] = implode(',', $headers);

        // Data rows
        foreach ($matrix['cohorts'] as $cohort) {
            $row = [
                $cohort['cohort_label'],
                '"' . str_replace('"', '""', $cohort['plan_name']) . '"',
                $cohort['cohort_size'],
            ];

            for ($i = 1; $i <= 6; $i++) {
                $row[] = $cohort['retention'][$i] ?? '-';
            }

            $lines[] = implode(',', $row);
        }

        $content = implode("\n", $lines);
        $filename = 'cohort_analysis_' . now()->format('Y-m-d_His') . '.csv';

        return [
            'content' => $content,
            'filename' => $filename,
        ];
    }

    /**
     * Clear all cohort analytics cache
     */
    public function clearCache(): void
    {
        $patterns = [
            'admin.cohort.matrix.*',
            'admin.cohort.retention_by_plan.*',
            'admin.cohort.churn_analysis',
            'admin.cohort.plan_summary',
        ];

        foreach ($patterns as $pattern) {
            // For simple cache drivers, we iterate known keys
            Cache::forget($pattern);
        }

        // Also clear specific keys we know about
        for ($months = 1; $months <= 12; $months++) {
            for ($retention = 1; $retention <= 12; $retention++) {
                Cache::forget("admin.cohort.matrix..{$months}.{$retention}");
                Cache::forget("admin.cohort.retention_by_plan.{$retention}");
            }
        }

        Cache::forget('admin.cohort.churn_analysis');
        Cache::forget('admin.cohort.plan_summary');
    }
}

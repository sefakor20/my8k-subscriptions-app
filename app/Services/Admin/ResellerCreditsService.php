<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\ResellerCreditLog;
use App\Services\My8kApiClient;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ResellerCreditsService
{
    /**
     * Cache duration in seconds (5 minutes for balance checks)
     */
    private const CACHE_DURATION = 300;

    /**
     * Low balance thresholds for alerts
     */
    private const THRESHOLD_WARNING = 500;

    private const THRESHOLD_CRITICAL = 200;

    private const THRESHOLD_URGENT = 50;

    public function __construct(
        private My8kApiClient $my8kClient,
    ) {}

    /**
     * Get current credit balance from My8K API
     */
    public function getCurrentBalance(): float
    {
        return Cache::remember(
            'reseller.credits.current_balance',
            self::CACHE_DURATION,
            function (): float {
                try {
                    $response = $this->my8kClient->getResellerInfo();

                    if ($response['success'] && isset($response['data']['credits'])) {
                        return (float) $response['data']['credits'];
                    }

                    Log::warning('My8K API did not return credits field', ['response' => $response]);

                    return 0.0;
                } catch (Exception $e) {
                    Log::error('Failed to fetch reseller balance', [
                        'error' => $e->getMessage(),
                    ]);

                    return 0.0;
                }
            },
        );
    }

    /**
     * Log a balance snapshot to the database
     */
    public function logBalanceSnapshot(?string $reason = null, ?int $provisioningLogId = null): ResellerCreditLog
    {
        try {
            $response = $this->my8kClient->getResellerInfo();
            $currentBalance = ($response['success'] && isset($response['data']['credits']))
                ? (float) $response['data']['credits']
                : 0.0;

            $lastLog = ResellerCreditLog::latest()->first();
            $previousBalance = $lastLog?->balance;
            $changeAmount = null;
            $changeType = 'snapshot';

            if ($previousBalance !== null) {
                $changeAmount = $currentBalance - $previousBalance;

                if ($changeAmount < 0) {
                    $changeType = 'debit';
                    $changeAmount = abs($changeAmount);
                } elseif ($changeAmount > 0) {
                    $changeType = 'credit';
                }
            }

            $log = ResellerCreditLog::create([
                'balance' => $currentBalance,
                'previous_balance' => $previousBalance,
                'change_amount' => $changeAmount,
                'change_type' => $changeType,
                'reason' => $reason ?? 'Scheduled balance check',
                'related_provisioning_log_id' => $provisioningLogId,
                'api_response' => $response['data'] ?? $response,
            ]);

            // Clear cache after logging
            Cache::forget('reseller.credits.current_balance');

            return $log;
        } catch (Exception $e) {
            Log::error('Failed to log balance snapshot', [
                'error' => $e->getMessage(),
                'reason' => $reason,
            ]);

            throw $e;
        }
    }

    /**
     * Get balance history time series
     *
     * @return array{labels: array<string>, data: array<float>}
     */
    public function getBalanceHistory(int $days = 30): array
    {
        return Cache::remember(
            "reseller.credits.balance_history.{$days}d",
            self::CACHE_DURATION,
            function () use ($days): array {
                $startDate = now()->subDays($days - 1)->startOfDay();
                $endDate = now()->endOfDay();

                $period = CarbonPeriod::create($startDate, '1 day', $endDate);

                $labels = [];
                $data = [];

                foreach ($period as $date) {
                    $dayEnd = $date->copy()->endOfDay();

                    // Get the last balance snapshot for this day
                    $log = ResellerCreditLog::where('created_at', '<=', $dayEnd)
                        ->latest()
                        ->first();

                    $labels[] = $date->format('M d');
                    $data[] = $log?->balance ?? 0;
                }

                return [
                    'labels' => $labels,
                    'data' => $data,
                ];
            },
        );
    }

    /**
     * Get daily usage patterns
     *
     * @return array{labels: array<string>, data: array<float>}
     */
    public function getDailyUsage(int $days = 30): array
    {
        return Cache::remember(
            "reseller.credits.daily_usage.{$days}d",
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

                    // Get all debits (usage) for this day
                    $usage = ResellerCreditLog::whereBetween('created_at', [$dayStart, $dayEnd])
                        ->debits()
                        ->sum('change_amount');

                    $labels[] = $date->format('M d');
                    $data[] = (float) $usage;
                }

                return [
                    'labels' => $labels,
                    'data' => $data,
                ];
            },
        );
    }

    /**
     * Calculate usage metrics
     *
     * @return array{
     *     currentBalance: float,
     *     change24h: float,
     *     change7d: float,
     *     avgDailyUsage: float,
     *     estimatedDepletionDays: int|null,
     *     alertLevel: string
     * }
     */
    public function calculateUsageMetrics(): array
    {
        return Cache::remember(
            'reseller.credits.usage_metrics',
            self::CACHE_DURATION,
            function (): array {
                $currentBalance = $this->getCurrentBalance();

                // Get balance 24 hours ago
                $log24h = ResellerCreditLog::where('created_at', '<=', now()->subDay())
                    ->latest()
                    ->first();
                $change24h = $log24h ? ($currentBalance - $log24h->balance) : 0;

                // Get balance 7 days ago
                $log7d = ResellerCreditLog::where('created_at', '<=', now()->subDays(7))
                    ->latest()
                    ->first();
                $change7d = $log7d ? ($currentBalance - $log7d->balance) : 0;

                // Calculate average daily usage (last 30 days)
                $avgDailyUsage = ResellerCreditLog::where('created_at', '>=', now()->subDays(30))
                    ->debits()
                    ->avg('change_amount') ?? 0;

                // Estimate depletion date
                $estimatedDepletionDays = null;
                if ($avgDailyUsage > 0) {
                    $estimatedDepletionDays = (int) ceil($currentBalance / $avgDailyUsage);
                }

                // Determine alert level
                $alertLevel = $this->determineAlertLevel($currentBalance);

                return [
                    'currentBalance' => $currentBalance,
                    'change24h' => round($change24h, 2),
                    'change7d' => round($change7d, 2),
                    'avgDailyUsage' => round($avgDailyUsage, 2),
                    'estimatedDepletionDays' => $estimatedDepletionDays,
                    'alertLevel' => $alertLevel,
                ];
            },
        );
    }

    /**
     * Determine alert level based on balance
     */
    public function determineAlertLevel(float $balance): string
    {
        if ($balance <= self::THRESHOLD_URGENT) {
            return 'urgent';
        }

        if ($balance <= self::THRESHOLD_CRITICAL) {
            return 'critical';
        }

        if ($balance <= self::THRESHOLD_WARNING) {
            return 'warning';
        }

        return 'ok';
    }

    /**
     * Check if balance requires an alert
     */
    public function shouldTriggerAlert(float $balance): bool
    {
        return $balance <= self::THRESHOLD_WARNING;
    }

    /**
     * Get alert threshold values
     *
     * @return array{warning: int, critical: int, urgent: int}
     */
    public function getAlertThresholds(): array
    {
        return [
            'warning' => self::THRESHOLD_WARNING,
            'critical' => self::THRESHOLD_CRITICAL,
            'urgent' => self::THRESHOLD_URGENT,
        ];
    }

    /**
     * Clear all credits-related caches
     */
    public function clearCache(): void
    {
        Cache::forget('reseller.credits.current_balance');
        Cache::forget('reseller.credits.usage_metrics');

        // Clear time-series caches
        for ($days = 1; $days <= 90; $days++) {
            Cache::forget("reseller.credits.balance_history.{$days}d");
            Cache::forget("reseller.credits.daily_usage.{$days}d");
        }
    }
}

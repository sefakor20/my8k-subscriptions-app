<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\ResellerCreditLog;
use App\Services\Admin\ResellerCreditsService;
use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ResellerCreditsManagement extends Component
{
    use WithPagination;

    public int $dateRange = 30;

    public string $filterType = 'all';

    public ?string $metricsError = null;

    public ?string $historyError = null;

    public ?string $usageError = null;

    /**
     * Get usage metrics
     */
    #[Computed]
    public function metrics(): array
    {
        try {
            $metrics = app(ResellerCreditsService::class)->calculateUsageMetrics();
            $this->metricsError = $metrics['error'] ?? null;

            return $metrics;
        } catch (Exception $e) {
            Log::error('Failed to load metrics', ['error' => $e->getMessage()]);
            $this->metricsError = 'Unable to load metrics';

            return [
                'currentBalance' => 0.0,
                'change24h' => 0.0,
                'change7d' => 0.0,
                'avgDailyUsage' => 0.0,
                'estimatedDepletionDays' => null,
                'alertLevel' => 'unknown',
                'error' => $this->metricsError,
            ];
        }
    }

    /**
     * Get balance history for charts
     */
    #[Computed]
    public function balanceHistory(): array
    {
        try {
            $history = app(ResellerCreditsService::class)->getBalanceHistory($this->dateRange);
            $this->historyError = $history['error'] ?? null;

            return $history;
        } catch (Exception $e) {
            Log::error('Failed to load balance history', ['error' => $e->getMessage()]);
            $this->historyError = 'Unable to load balance history';

            return [
                'labels' => [],
                'data' => [],
                'error' => $this->historyError,
            ];
        }
    }

    /**
     * Get daily usage data for charts
     */
    #[Computed]
    public function dailyUsage(): array
    {
        try {
            $usage = app(ResellerCreditsService::class)->getDailyUsage($this->dateRange);
            $this->usageError = $usage['error'] ?? null;

            return $usage;
        } catch (Exception $e) {
            Log::error('Failed to load daily usage', ['error' => $e->getMessage()]);
            $this->usageError = 'Unable to load usage data';

            return [
                'labels' => [],
                'data' => [],
                'error' => $this->usageError,
            ];
        }
    }

    /**
     * Get alert thresholds
     */
    #[Computed]
    public function thresholds(): array
    {
        return app(ResellerCreditsService::class)->getAlertThresholds();
    }

    /**
     * Refresh balance from API
     */
    public function refreshBalance(): void
    {
        try {
            $result = app(ResellerCreditsService::class)->logBalanceSnapshot('Manual refresh from credits page');

            if ($result === null) {
                $this->metricsError = 'Failed to refresh from My8K API';
            } else {
                app(ResellerCreditsService::class)->clearCache();
                $this->metricsError = null;
                $this->historyError = null;
                $this->usageError = null;

                unset($this->metrics);
                unset($this->balanceHistory);
                unset($this->dailyUsage);

                $this->dispatch('balance-refreshed');
                $this->resetPage();
            }
        } catch (Exception $e) {
            Log::error('Failed to refresh', ['error' => $e->getMessage()]);
            $this->metricsError = 'Failed to refresh: ' . $e->getMessage();
        }
    }

    /**
     * Update date range filter
     */
    public function updatedDateRange(): void
    {
        unset($this->balanceHistory);
        unset($this->dailyUsage);
        $this->resetPage();
    }

    /**
     * Update filter type
     */
    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        $query = ResellerCreditLog::query()
            ->when($this->filterType !== 'all', function ($q) {
                $q->where('change_type', $this->filterType);
            })
            ->latest();

        return view('livewire.admin.reseller-credits-management', [
            'logs' => $query->paginate(20),
        ])->layout('components.layouts.app');
    }
}

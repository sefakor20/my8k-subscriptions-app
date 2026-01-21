<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\Admin\AnalyticsService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Analytics extends Component
{
    public string $dateRangeType = 'preset';

    public int $dateRange = 30;

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public bool $autoRefresh = true;

    /**
     * Get the effective date range parameters
     *
     * @return array{days: int|null, startDate: string|null, endDate: string|null}
     */
    private function getDateParams(): array
    {
        if ($this->dateRangeType === 'custom' && $this->customStartDate && $this->customEndDate) {
            return [
                'days' => null,
                'startDate' => $this->customStartDate,
                'endDate' => $this->customEndDate,
            ];
        }

        return [
            'days' => $this->dateRange,
            'startDate' => null,
            'endDate' => null,
        ];
    }

    /**
     * Get success rate time series data
     */
    #[Computed]
    public function successRateData(): array
    {
        $params = $this->getDateParams();

        return app(AnalyticsService::class)->getSuccessRateTimeSeries(
            $params['days'],
            $params['startDate'],
            $params['endDate'],
        );
    }

    /**
     * Get order status distribution data
     */
    #[Computed]
    public function orderStatusData(): array
    {
        return app(AnalyticsService::class)->getOrderStatusDistribution();
    }

    /**
     * Get error type frequency data
     */
    #[Computed]
    public function errorFrequencyData(): array
    {
        $params = $this->getDateParams();

        return app(AnalyticsService::class)->getErrorTypeFrequency(
            $params['days'],
            $params['startDate'],
            $params['endDate'],
        );
    }

    /**
     * Get provisioning performance metrics
     */
    #[Computed]
    public function performanceMetrics(): array
    {
        $params = $this->getDateParams();

        return app(AnalyticsService::class)->getProvisioningPerformance(
            $params['days'],
            $params['startDate'],
            $params['endDate'],
        );
    }

    /**
     * Get subscription growth data
     */
    #[Computed]
    public function subscriptionGrowthData(): array
    {
        $params = $this->getDateParams();

        return app(AnalyticsService::class)->getSubscriptionGrowth(
            $params['days'],
            $params['startDate'],
            $params['endDate'],
        );
    }

    /**
     * Get revenue time series data
     */
    #[Computed]
    public function revenueData(): array
    {
        $params = $this->getDateParams();

        return app(AnalyticsService::class)->getRevenueTimeSeries(
            $params['days'],
            $params['startDate'],
            $params['endDate'],
        );
    }

    /**
     * Apply custom date range
     */
    public function applyCustomDateRange(): void
    {
        if (! $this->customStartDate || ! $this->customEndDate) {
            return;
        }

        $this->dateRangeType = 'custom';
        $this->clearComputedProperties();
    }

    /**
     * Set a preset date range
     */
    public function setPresetDateRange(int $days): void
    {
        $this->dateRange = $days;
        $this->dateRangeType = 'preset';
        $this->customStartDate = null;
        $this->customEndDate = null;
        $this->clearComputedProperties();
    }

    /**
     * Switch to preset date range
     */
    public function usePresetDateRange(): void
    {
        $this->dateRangeType = 'preset';
        $this->customStartDate = null;
        $this->customEndDate = null;
        $this->clearComputedProperties();
    }

    /**
     * Update date range filter
     */
    public function updatedDateRange(): void
    {
        $this->dateRangeType = 'preset';
        $this->clearComputedProperties();
    }

    /**
     * Clear all computed properties
     */
    private function clearComputedProperties(): void
    {
        unset($this->successRateData);
        unset($this->orderStatusData);
        unset($this->errorFrequencyData);
        unset($this->performanceMetrics);
        unset($this->subscriptionGrowthData);
        unset($this->revenueData);
    }

    /**
     * Export analytics data as CSV
     */
    public function exportCsv(): StreamedResponse
    {
        $params = $this->getDateParams();

        $export = app(AnalyticsService::class)->exportToCsv(
            $params['days'],
            $params['startDate'],
            $params['endDate'],
        );

        return response()->streamDownload(
            fn() => print($export['content']),
            $export['filename'],
            [
                'Content-Type' => 'text/csv',
            ],
        );
    }

    /**
     * Refresh all analytics data
     */
    public function refreshData(): void
    {
        // Only refresh if auto-refresh is enabled
        if (! $this->autoRefresh) {
            return;
        }

        app(AnalyticsService::class)->clearCache();

        $this->clearComputedProperties();

        $this->dispatch('analytics-refreshed');
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.analytics')
            ->layout('components.layouts.app');
    }
}

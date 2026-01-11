<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\Admin\AnalyticsService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Analytics extends Component
{
    public int $dateRange = 30;

    public bool $autoRefresh = true;

    /**
     * Get success rate time series data
     */
    #[Computed]
    public function successRateData(): array
    {
        return app(AnalyticsService::class)->getSuccessRateTimeSeries($this->dateRange);
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
        return app(AnalyticsService::class)->getErrorTypeFrequency($this->dateRange);
    }

    /**
     * Get provisioning performance metrics
     */
    #[Computed]
    public function performanceMetrics(): array
    {
        return app(AnalyticsService::class)->getProvisioningPerformance($this->dateRange);
    }

    /**
     * Get subscription growth data
     */
    #[Computed]
    public function subscriptionGrowthData(): array
    {
        return app(AnalyticsService::class)->getSubscriptionGrowth($this->dateRange);
    }

    /**
     * Get revenue time series data
     */
    #[Computed]
    public function revenueData(): array
    {
        return app(AnalyticsService::class)->getRevenueTimeSeries($this->dateRange);
    }

    /**
     * Update date range filter
     */
    public function updatedDateRange(): void
    {
        // Clear computed properties when date range changes
        unset($this->successRateData);
        unset($this->orderStatusData);
        unset($this->errorFrequencyData);
        unset($this->performanceMetrics);
        unset($this->subscriptionGrowthData);
        unset($this->revenueData);
    }

    /**
     * Refresh all analytics data
     */
    public function refreshData(): void
    {
        // Only refresh if auto-refresh is enabled
        if (!$this->autoRefresh) {
            return;
        }

        app(AnalyticsService::class)->clearCache();

        // Clear computed properties
        unset($this->successRateData);
        unset($this->orderStatusData);
        unset($this->errorFrequencyData);
        unset($this->performanceMetrics);
        unset($this->subscriptionGrowthData);
        unset($this->revenueData);

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

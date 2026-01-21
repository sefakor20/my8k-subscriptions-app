<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\Admin\CouponAnalyticsService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CouponAnalytics extends Component
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
     * Get coupon metrics
     */
    #[Computed]
    public function metrics(): array
    {
        $params = $this->getDateParams();

        return app(CouponAnalyticsService::class)->getMetrics(
            $params['days'],
            $params['startDate'],
            $params['endDate'],
        );
    }

    /**
     * Get redemptions time series data
     */
    #[Computed]
    public function redemptionsData(): array
    {
        $params = $this->getDateParams();

        return app(CouponAnalyticsService::class)->getRedemptionsTimeSeries(
            $params['days'],
            $params['startDate'],
            $params['endDate'],
        );
    }

    /**
     * Get discount amount time series data
     */
    #[Computed]
    public function discountData(): array
    {
        $params = $this->getDateParams();

        return app(CouponAnalyticsService::class)->getDiscountTimeSeries(
            $params['days'],
            $params['startDate'],
            $params['endDate'],
        );
    }

    /**
     * Get top coupons data
     */
    #[Computed]
    public function topCouponsData(): array
    {
        $params = $this->getDateParams();

        return app(CouponAnalyticsService::class)->getTopCoupons(
            $params['days'],
            $params['startDate'],
            $params['endDate'],
        );
    }

    /**
     * Get discount type distribution data
     */
    #[Computed]
    public function discountTypeData(): array
    {
        $params = $this->getDateParams();

        return app(CouponAnalyticsService::class)->getDiscountTypeDistribution(
            $params['days'],
            $params['startDate'],
            $params['endDate'],
        );
    }

    /**
     * Get currency distribution data
     */
    #[Computed]
    public function currencyData(): array
    {
        $params = $this->getDateParams();

        return app(CouponAnalyticsService::class)->getCurrencyDistribution(
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
        unset($this->metrics);
        unset($this->redemptionsData);
        unset($this->discountData);
        unset($this->topCouponsData);
        unset($this->discountTypeData);
        unset($this->currencyData);
    }

    /**
     * Refresh all analytics data
     */
    public function refreshData(): void
    {
        if (! $this->autoRefresh) {
            return;
        }

        app(CouponAnalyticsService::class)->clearCache();

        $this->clearComputedProperties();

        $this->dispatch('coupon-analytics-refreshed');
    }

    /**
     * Export analytics data as CSV
     */
    public function exportCsv(): StreamedResponse
    {
        $params = $this->getDateParams();

        $export = app(CouponAnalyticsService::class)->exportToCsv(
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
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.coupon-analytics')
            ->layout('components.layouts.app');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\Admin\CouponAnalyticsService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CouponAnalytics extends Component
{
    public int $dateRange = 30;

    public bool $autoRefresh = true;

    /**
     * Get coupon metrics
     */
    #[Computed]
    public function metrics(): array
    {
        return app(CouponAnalyticsService::class)->getMetrics($this->dateRange);
    }

    /**
     * Get redemptions time series data
     */
    #[Computed]
    public function redemptionsData(): array
    {
        return app(CouponAnalyticsService::class)->getRedemptionsTimeSeries($this->dateRange);
    }

    /**
     * Get discount amount time series data
     */
    #[Computed]
    public function discountData(): array
    {
        return app(CouponAnalyticsService::class)->getDiscountTimeSeries($this->dateRange);
    }

    /**
     * Get top coupons data
     */
    #[Computed]
    public function topCouponsData(): array
    {
        return app(CouponAnalyticsService::class)->getTopCoupons($this->dateRange);
    }

    /**
     * Get discount type distribution data
     */
    #[Computed]
    public function discountTypeData(): array
    {
        return app(CouponAnalyticsService::class)->getDiscountTypeDistribution($this->dateRange);
    }

    /**
     * Get currency distribution data
     */
    #[Computed]
    public function currencyData(): array
    {
        return app(CouponAnalyticsService::class)->getCurrencyDistribution($this->dateRange);
    }

    /**
     * Update date range filter
     */
    public function updatedDateRange(): void
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

        unset($this->metrics);
        unset($this->redemptionsData);
        unset($this->discountData);
        unset($this->topCouponsData);
        unset($this->discountTypeData);
        unset($this->currencyData);

        $this->dispatch('coupon-analytics-refreshed');
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

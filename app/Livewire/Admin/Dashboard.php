<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\Admin\DashboardStatsService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * Get active subscriptions count
     */
    #[Computed]
    public function activeSubscriptions(): int
    {
        return app(DashboardStatsService::class)->getActiveSubscriptionsCount();
    }

    /**
     * Get orders today count
     */
    #[Computed]
    public function ordersToday(): int
    {
        return app(DashboardStatsService::class)->getOrdersTodayCount();
    }

    /**
     * Get provisioning success rate
     */
    #[Computed]
    public function successRate(): float
    {
        return app(DashboardStatsService::class)->getProvisioningSuccessRate();
    }

    /**
     * Get failed jobs count
     */
    #[Computed]
    public function failedJobs(): int
    {
        return app(DashboardStatsService::class)->getFailedJobsCount();
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.dashboard')
            ->layout('components.layouts.app');
    }
}

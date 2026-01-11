<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\ResellerCreditLog;
use App\Services\Admin\ResellerCreditsService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ResellerCreditsManagement extends Component
{
    use WithPagination;

    public int $dateRange = 30;

    public string $filterType = 'all';

    /**
     * Get usage metrics
     */
    #[Computed]
    public function metrics(): array
    {
        return app(ResellerCreditsService::class)->calculateUsageMetrics();
    }

    /**
     * Get balance history for charts
     */
    #[Computed]
    public function balanceHistory(): array
    {
        return app(ResellerCreditsService::class)->getBalanceHistory($this->dateRange);
    }

    /**
     * Get daily usage data for charts
     */
    #[Computed]
    public function dailyUsage(): array
    {
        return app(ResellerCreditsService::class)->getDailyUsage($this->dateRange);
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
        app(ResellerCreditsService::class)->clearCache();
        app(ResellerCreditsService::class)->logBalanceSnapshot('Manual refresh from credits page');

        unset($this->metrics);
        unset($this->balanceHistory);
        unset($this->dailyUsage);

        $this->dispatch('balance-refreshed');
        $this->resetPage();
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

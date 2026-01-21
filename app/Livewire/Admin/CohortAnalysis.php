<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\Admin\CohortAnalyticsService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CohortAnalysis extends Component
{
    public ?string $selectedPlanId = null;

    public int $cohortMonths = 12;

    public int $retentionMonths = 6;

    public bool $autoRefresh = true;

    /**
     * Get cohort retention matrix data
     */
    #[Computed]
    public function cohortMatrix(): array
    {
        return app(CohortAnalyticsService::class)->getCohortRetentionMatrix(
            $this->selectedPlanId,
            $this->cohortMonths,
            $this->retentionMonths,
        );
    }

    /**
     * Get retention comparison by plan
     */
    #[Computed]
    public function retentionByPlan(): array
    {
        return app(CohortAnalyticsService::class)->getRetentionByPlan($this->retentionMonths);
    }

    /**
     * Get churn analysis data
     */
    #[Computed]
    public function churnAnalysis(): array
    {
        return app(CohortAnalyticsService::class)->getChurnAnalysisByPlan();
    }

    /**
     * Get plan retention summary
     */
    #[Computed]
    public function planSummary(): array
    {
        return app(CohortAnalyticsService::class)->getPlanRetentionSummary();
    }

    /**
     * Get available plans for filtering
     */
    #[Computed]
    public function availablePlans(): Collection
    {
        return app(CohortAnalyticsService::class)->getAvailablePlans();
    }

    /**
     * Set selected plan filter
     */
    public function setSelectedPlan(?string $planId): void
    {
        $this->selectedPlanId = $planId;
        $this->clearComputedProperties();
    }

    /**
     * Clear all computed properties
     */
    private function clearComputedProperties(): void
    {
        unset($this->cohortMatrix);
        unset($this->retentionByPlan);
        unset($this->churnAnalysis);
        unset($this->planSummary);
    }

    /**
     * Export cohort data as CSV
     */
    public function exportCsv(): StreamedResponse
    {
        $export = app(CohortAnalyticsService::class)->exportToCsv($this->selectedPlanId);

        return response()->streamDownload(
            fn() => print($export['content']),
            $export['filename'],
            [
                'Content-Type' => 'text/csv',
            ],
        );
    }

    /**
     * Refresh all cohort data
     */
    public function refreshData(): void
    {
        if (! $this->autoRefresh) {
            return;
        }

        app(CohortAnalyticsService::class)->clearCache();

        $this->clearComputedProperties();

        $this->dispatch('cohort-refreshed');
    }

    /**
     * Get color class for retention percentage
     */
    public function getRetentionColorClass(?float $value): string
    {
        if ($value === null) {
            return 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400';
        }

        if ($value >= 80) {
            return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400';
        }

        if ($value >= 60) {
            return 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400';
        }

        if ($value >= 40) {
            return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400';
        }

        if ($value >= 20) {
            return 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400';
        }

        return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400';
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.cohort-analysis')
            ->layout('components.layouts.app');
    }
}

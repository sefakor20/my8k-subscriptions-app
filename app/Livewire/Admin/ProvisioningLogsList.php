<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\Admin\ProvisioningLogsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProvisioningLogsList extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'action')]
    public string $actionFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'search')]
    public string $search = '';

    public int $perPage = 50;

    /**
     * Get filtered and paginated provisioning logs
     */
    #[Computed]
    public function logs(): LengthAwarePaginator
    {
        $service = app(ProvisioningLogsService::class);

        return $service->getLogsWithFilters([
            'status' => $this->statusFilter,
            'action' => $this->actionFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'search' => $this->search,
        ], $this->perPage);
    }

    /**
     * Get available statuses for filter
     */
    #[Computed]
    public function statuses(): array
    {
        $service = app(ProvisioningLogsService::class);

        return $service->getDistinctStatuses();
    }

    /**
     * Get available actions for filter
     */
    #[Computed]
    public function actions(): array
    {
        $service = app(ProvisioningLogsService::class);

        return $service->getDistinctActions();
    }

    /**
     * Reset all filters
     */
    public function resetFilters(): void
    {
        $this->statusFilter = '';
        $this->actionFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->search = '';
        $this->resetPage();
    }

    /**
     * Show detail modal for a log
     */
    public function showDetail(string $logId): void
    {
        $this->dispatch('open-log-modal', logId: $logId);
    }

    /**
     * Update filters and reset pagination
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedActionFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.provisioning-logs-list')
            ->layout('components.layouts.app');
    }
}

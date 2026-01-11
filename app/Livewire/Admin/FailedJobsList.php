<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\Admin\FailedJobsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class FailedJobsList extends Component
{
    use WithPagination;

    #[Url(as: 'job_type')]
    public string $jobTypeFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'error')]
    public string $errorSearch = '';

    public int $perPage = 50;

    public array $selectedIds = [];

    public bool $selectAll = false;

    public ?string $selectedJobUuid = null;

    /**
     * Get filtered and paginated failed jobs
     */
    #[Computed]
    public function failedJobs(): LengthAwarePaginator
    {
        $service = app(FailedJobsService::class);

        return $service->getFailedJobsWithFilters([
            'job_type' => $this->jobTypeFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'error_search' => $this->errorSearch,
        ], $this->perPage);
    }

    /**
     * Get distinct job types for filter
     */
    #[Computed]
    public function jobTypes(): Collection
    {
        $service = app(FailedJobsService::class);

        return $service->getDistinctJobTypes();
    }

    /**
     * Reset all filters
     */
    public function resetFilters(): void
    {
        $this->jobTypeFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->errorSearch = '';
        $this->resetPage();
    }

    /**
     * Toggle select all
     */
    public function toggleSelectAll(): void
    {
        $this->selectAll = ! $this->selectAll;

        if ($this->selectAll) {
            $this->selectedIds = $this->failedJobs->pluck('uuid')->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    /**
     * Toggle individual selection
     */
    public function toggleSelect(string $uuid): void
    {
        if (in_array($uuid, $this->selectedIds)) {
            $this->selectedIds = array_diff($this->selectedIds, [$uuid]);
        } else {
            $this->selectedIds[] = $uuid;
        }
    }

    /**
     * Retry selected jobs
     */
    public function retrySelected(): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('error', 'No jobs selected.');

            return;
        }

        $service = app(FailedJobsService::class);
        $service->retryJobs($this->selectedIds);

        session()->flash('success', count($this->selectedIds) . ' job(s) retry initiated.');
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    /**
     * Delete selected jobs
     */
    public function deleteSelected(): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('error', 'No jobs selected.');

            return;
        }

        $service = app(FailedJobsService::class);
        $service->deleteJobs($this->selectedIds);

        session()->flash('success', count($this->selectedIds) . ' job(s) deleted successfully.');
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    /**
     * Retry all failed jobs
     */
    public function retryAll(): void
    {
        $service = app(FailedJobsService::class);
        $count = $service->getCount();

        if ($count === 0) {
            session()->flash('error', 'No failed jobs to retry.');

            return;
        }

        $service->retryAll();

        session()->flash('success', "Retry initiated for all {$count} failed job(s).");
    }

    /**
     * Delete all failed jobs
     */
    public function deleteAll(): void
    {
        $service = app(FailedJobsService::class);
        $count = $service->getCount();

        if ($count === 0) {
            session()->flash('error', 'No failed jobs to delete.');

            return;
        }

        $service->deleteAll();

        session()->flash('success', "All {$count} failed job(s) deleted successfully.");
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    /**
     * Show job detail modal
     */
    public function showDetail(string $uuid): void
    {
        $this->selectedJobUuid = $uuid;
        $this->dispatch('open-failed-job-modal', uuid: $uuid);
    }

    /**
     * Update filters and reset pagination
     */
    public function updatedJobTypeFilter(): void
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

    public function updatedErrorSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.failed-jobs-list')
            ->layout('components.layouts.app');
    }
}

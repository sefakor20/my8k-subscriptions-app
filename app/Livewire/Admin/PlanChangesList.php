<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\PlanChangeStatus;
use App\Models\PlanChange;
use App\Services\PlanChangeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PlanChangesList extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'type')]
    public string $typeFilter = '';

    public int $perPage = 50;

    /**
     * Get filtered and paginated plan changes.
     */
    #[Computed]
    public function planChanges(): LengthAwarePaginator
    {
        $query = PlanChange::query()
            ->with(['user', 'subscription', 'fromPlan', 'toPlan', 'order']);

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('user', function ($userQuery) {
                    $userQuery->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                })
                ->orWhereHas('fromPlan', function ($planQuery) {
                    $planQuery->where('name', 'like', "%{$this->search}%");
                })
                ->orWhereHas('toPlan', function ($planQuery) {
                    $planQuery->where('name', 'like', "%{$this->search}%");
                });
            });
        }

        // Status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Type filter
        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        return $query->latest()->paginate($this->perPage);
    }

    /**
     * Get all available plan change statuses.
     */
    #[Computed]
    public function statuses(): array
    {
        return PlanChangeStatus::cases();
    }

    /**
     * Reset all filters.
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->typeFilter = '';
        $this->resetPage();
    }

    /**
     * Cancel a plan change.
     */
    public function cancelPlanChange(string $planChangeId): void
    {
        $planChange = PlanChange::findOrFail($planChangeId);
        $planChangeService = app(PlanChangeService::class);

        if ($planChangeService->cancelChange($planChange)) {
            session()->flash('success', 'Plan change has been cancelled.');
        } else {
            session()->flash('error', 'Unable to cancel this plan change.');
        }
    }

    /**
     * Update search query and reset pagination.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Update status filter and reset pagination.
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Update type filter and reset pagination.
     */
    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.plan-changes-list')
            ->layout('components.layouts.app');
    }
}

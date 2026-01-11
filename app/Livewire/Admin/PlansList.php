<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\Admin\PlansService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Exception;

class PlansList extends Component
{
    public ?bool $activeFilter = null;

    public ?string $selectedPlanId = null;

    /**
     * Get all plans with optional active filter
     */
    #[Computed]
    public function plans(): Collection
    {
        $service = app(PlansService::class);

        return $service->getPlans($this->activeFilter);
    }

    /**
     * Show create plan modal
     */
    public function createPlan(): void
    {
        $this->dispatch('open-plan-form-modal', mode: 'create');
    }

    /**
     * Show edit plan modal
     */
    public function editPlan(string $planId): void
    {
        $this->selectedPlanId = $planId;
        $this->dispatch('open-plan-form-modal', mode: 'edit', planId: $planId);
    }

    /**
     * Toggle plan active status
     */
    public function toggleActive(string $planId): void
    {
        $service = app(PlansService::class);
        $service->toggleActive($planId);

        unset($this->plans);
        session()->flash('success', 'Plan status updated successfully.');
    }

    /**
     * Delete a plan
     */
    public function deletePlan(string $planId): void
    {
        try {
            $service = app(PlansService::class);
            $service->deletePlan($planId);

            unset($this->plans);
            session()->flash('success', 'Plan deleted successfully.');
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Check if plan can be deleted
     */
    public function canDelete(string $planId): bool
    {
        $service = app(PlansService::class);

        return $service->canDelete($planId);
    }

    /**
     * Filter by active status
     */
    public function filterActive(?bool $active): void
    {
        $this->activeFilter = $active;
        unset($this->plans);
    }

    /**
     * Refresh plans list after form submission
     */
    #[On('plan-saved')]
    public function refreshPlans(): void
    {
        unset($this->plans);
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.plans-list')
            ->layout('components.layouts.app');
    }
}

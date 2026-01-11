<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Admin\SubscriptionManagementService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SubscriptionsList extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'plan')]
    public string $planFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    public int $perPage = 50;

    public ?string $selectedSubscriptionId = null;

    /**
     * Get filtered and paginated subscriptions
     */
    #[Computed]
    public function subscriptions(): LengthAwarePaginator
    {
        $service = app(SubscriptionManagementService::class);

        return $service->getSubscriptionsWithFilters([
            'search' => $this->search,
            'status' => $this->statusFilter,
            'plan_id' => $this->planFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], $this->perPage);
    }

    /**
     * Get all available plans for filter
     */
    #[Computed]
    public function plans(): \Illuminate\Database\Eloquent\Collection
    {
        return Plan::orderBy('name')->get();
    }

    /**
     * Get all available subscription statuses
     */
    #[Computed]
    public function statuses(): array
    {
        return SubscriptionStatus::cases();
    }

    /**
     * Reset all filters
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->planFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    /**
     * Manual provision a subscription
     */
    public function manualProvision(string $subscriptionId): void
    {
        $service = app(SubscriptionManagementService::class);
        $service->retryProvisioning($subscriptionId);

        $this->dispatch('subscription-provisioned', subscriptionId: $subscriptionId);
        session()->flash('success', 'Provisioning job dispatched successfully.');
    }

    /**
     * Suspend a subscription
     */
    public function suspend(string $subscriptionId): void
    {
        $service = app(SubscriptionManagementService::class);
        $service->suspendSubscription($subscriptionId);

        $this->dispatch('subscription-updated', subscriptionId: $subscriptionId);
        session()->flash('success', 'Subscription suspended successfully.');
    }

    /**
     * Reactivate a subscription
     */
    public function reactivate(string $subscriptionId): void
    {
        $service = app(SubscriptionManagementService::class);
        $service->reactivateSubscription($subscriptionId);

        $this->dispatch('subscription-updated', subscriptionId: $subscriptionId);
        session()->flash('success', 'Subscription reactivated successfully.');
    }

    /**
     * Cancel a subscription
     */
    public function cancel(string $subscriptionId): void
    {
        $service = app(SubscriptionManagementService::class);
        $service->cancelSubscription($subscriptionId);

        $this->dispatch('subscription-updated', subscriptionId: $subscriptionId);
        session()->flash('success', 'Subscription cancelled successfully.');
    }

    /**
     * Show subscription detail modal
     */
    public function showDetail(string $subscriptionId): void
    {
        $this->selectedSubscriptionId = $subscriptionId;
        $this->dispatch('open-subscription-modal', subscriptionId: $subscriptionId);
    }

    /**
     * Update search query and reset pagination
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Update status filter and reset pagination
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Update plan filter and reset pagination
     */
    public function updatedPlanFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Update date from filter and reset pagination
     */
    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    /**
     * Update date to filter and reset pagination
     */
    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.subscriptions-list')
            ->layout('components.layouts.app');
    }
}

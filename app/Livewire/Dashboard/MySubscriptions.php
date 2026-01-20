<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MySubscriptions extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public int $perPage = 10;

    /**
     * Get user's subscriptions with filters
     */
    #[Computed]
    public function subscriptions(): LengthAwarePaginator
    {
        $query = auth()->user()->subscriptions()
            ->with(['plan', 'serviceAccount'])
            ->latest();

        // Apply status filter
        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        return $query->paginate($this->perPage);
    }

    /**
     * Get statistics for the dashboard
     */
    #[Computed]
    public function statistics(): array
    {
        $userId = auth()->id();

        return [
            'active' => Subscription::where('user_id', $userId)
                ->where('status', SubscriptionStatus::Active)
                ->count(),
            'expiring_soon' => Subscription::where('user_id', $userId)
                ->where('status', SubscriptionStatus::Active)
                ->where('expires_at', '<=', now()->addDays(7))
                ->where('expires_at', '>', now())
                ->count(),
            'expired' => Subscription::where('user_id', $userId)
                ->where('status', SubscriptionStatus::Expired)
                ->count(),
            'total' => Subscription::where('user_id', $userId)->count(),
        ];
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
     * Reset status filter
     */
    public function resetFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    /**
     * Show subscription detail
     */
    public function showDetail(string $subscriptionId): void
    {
        $this->dispatch('open-subscription-detail', subscriptionId: $subscriptionId);
    }

    /**
     * Open the change plan modal for a subscription
     */
    public function changePlan(string $subscriptionId): void
    {
        $this->dispatch('open-change-plan-modal', subscriptionId: $subscriptionId);
    }

    /**
     * Refresh the subscription list after a plan change
     */
    #[On('plan-changed')]
    #[On('plan-change-scheduled')]
    public function refreshSubscriptions(): void
    {
        unset($this->subscriptions);
        unset($this->statistics);
    }

    /**
     * Update status filter and reset pagination
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.my-subscriptions')
            ->layout('components.layouts.app');
    }
}

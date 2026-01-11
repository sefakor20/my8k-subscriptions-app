<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Subscription;
use App\Services\Admin\SubscriptionManagementService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SubscriptionDetailModal extends Component
{
    public bool $show = false;

    public ?string $subscriptionId = null;

    public int $extendDays = 30;

    public bool $showPassword = false;

    /**
     * Get subscription with all relationships
     */
    #[Computed]
    public function subscription(): ?Subscription
    {
        if (! $this->subscriptionId) {
            return null;
        }

        return Subscription::with([
            'user',
            'plan',
            'serviceAccount',
            'orders' => fn($query) => $query->latest()->limit(5),
            'provisioningLogs' => fn($query) => $query->latest()->limit(5),
        ])->find($this->subscriptionId);
    }

    /**
     * Open the modal
     */
    #[On('open-subscription-modal')]
    public function openModal(string $subscriptionId): void
    {
        $this->subscriptionId = $subscriptionId;
        $this->show = true;
        $this->showPassword = false;
    }

    /**
     * Close the modal
     */
    public function closeModal(): void
    {
        $this->show = false;
        $this->subscriptionId = null;
        $this->showPassword = false;
        $this->extendDays = 30;
    }

    /**
     * Toggle password visibility
     */
    public function togglePassword(): void
    {
        $this->showPassword = ! $this->showPassword;
    }

    /**
     * Retry provisioning
     */
    public function retryProvisioning(): void
    {
        if (! $this->subscriptionId) {
            return;
        }

        $service = app(SubscriptionManagementService::class);
        $service->retryProvisioning($this->subscriptionId);

        $this->dispatch('subscription-provisioned', subscriptionId: $this->subscriptionId);
        session()->flash('success', 'Provisioning job dispatched successfully.');
        $this->closeModal();
    }

    /**
     * Extend subscription
     */
    public function extend(): void
    {
        if (! $this->subscriptionId || $this->extendDays < 1) {
            return;
        }

        $service = app(SubscriptionManagementService::class);
        $service->extendSubscription($this->subscriptionId, $this->extendDays);

        $this->dispatch('subscription-updated', subscriptionId: $this->subscriptionId);
        session()->flash('success', "Subscription extended by {$this->extendDays} days.");
        $this->closeModal();
    }

    /**
     * Suspend subscription
     */
    public function suspend(): void
    {
        if (! $this->subscriptionId) {
            return;
        }

        $service = app(SubscriptionManagementService::class);
        $service->suspendSubscription($this->subscriptionId);

        $this->dispatch('subscription-updated', subscriptionId: $this->subscriptionId);
        session()->flash('success', 'Subscription suspended successfully.');
        $this->closeModal();
    }

    /**
     * Reactivate subscription
     */
    public function reactivate(): void
    {
        if (! $this->subscriptionId) {
            return;
        }

        $service = app(SubscriptionManagementService::class);
        $service->reactivateSubscription($this->subscriptionId);

        $this->dispatch('subscription-updated', subscriptionId: $this->subscriptionId);
        session()->flash('success', 'Subscription reactivated successfully.');
        $this->closeModal();
    }

    /**
     * Cancel subscription
     */
    public function cancel(): void
    {
        if (! $this->subscriptionId) {
            return;
        }

        $service = app(SubscriptionManagementService::class);
        $service->cancelSubscription($this->subscriptionId);

        $this->dispatch('subscription-updated', subscriptionId: $this->subscriptionId);
        session()->flash('success', 'Subscription cancelled successfully.');
        $this->closeModal();
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.subscription-detail-modal');
    }
}

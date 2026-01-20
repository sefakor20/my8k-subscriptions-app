<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\PaymentGateway;
use App\Mail\PlanChangeScheduled;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PaymentGatewayManager;
use App\Services\PlanChangeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class ChangePlanModal extends Component
{
    public bool $showModal = false;

    public ?string $subscriptionId = null;

    public ?string $selectedPlanId = null;

    public string $executionType = 'immediate';

    public ?string $selectedGateway = null;

    public ?array $prorationPreview = null;

    public bool $loading = false;

    public ?string $errorMessage = null;

    /**
     * Open the modal for a subscription.
     */
    #[On('open-change-plan-modal')]
    public function openModal(string $subscriptionId): void
    {
        $this->reset(['selectedPlanId', 'prorationPreview', 'errorMessage', 'executionType']);
        $this->subscriptionId = $subscriptionId;
        $this->selectedGateway = $this->availableGateways()->first()?->value;
        $this->showModal = true;
    }

    /**
     * Close the modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['subscriptionId', 'selectedPlanId', 'prorationPreview', 'errorMessage']);
    }

    /**
     * Get the current subscription.
     */
    #[Computed]
    public function subscription(): ?Subscription
    {
        if (! $this->subscriptionId) {
            return null;
        }

        return auth()->user()->subscriptions()
            ->with(['plan'])
            ->find($this->subscriptionId);
    }

    /**
     * Get available plans for switching.
     */
    #[Computed]
    public function availablePlans(): Collection
    {
        if (! $this->subscription) {
            return collect();
        }

        $planChangeService = app(PlanChangeService::class);

        return $planChangeService->getAvailablePlans($this->subscription);
    }

    /**
     * Get available payment gateways.
     */
    #[Computed]
    public function availableGateways(): Collection
    {
        $gatewayManager = app(PaymentGatewayManager::class);

        return collect($gatewayManager->getDirectGateways())
            ->map(fn($gateway) => $gateway->getIdentifier());
    }

    /**
     * Calculate proration when a plan is selected.
     */
    public function updatedSelectedPlanId(): void
    {
        $this->calculateProration();
    }

    /**
     * Calculate proration when gateway changes.
     */
    public function updatedSelectedGateway(): void
    {
        $this->calculateProration();
    }

    /**
     * Calculate proration preview.
     */
    public function calculateProration(): void
    {
        $this->prorationPreview = null;
        $this->errorMessage = null;

        if (! $this->subscription || ! $this->selectedPlanId) {
            return;
        }

        $plan = Plan::find($this->selectedPlanId);
        if (! $plan) {
            return;
        }

        $planChangeService = app(PlanChangeService::class);

        try {
            $this->prorationPreview = $planChangeService->calculateProration(
                $this->subscription,
                $plan,
                $this->selectedGateway,
            );
        } catch (Throwable $e) {
            $this->errorMessage = 'Unable to calculate proration. Please try again.';
        }
    }

    /**
     * Initiate the plan change.
     */
    public function initiatePlanChange(): void
    {
        $this->loading = true;
        $this->errorMessage = null;

        if (! $this->subscription || ! $this->selectedPlanId) {
            $this->errorMessage = 'Please select a plan.';
            $this->loading = false;

            return;
        }

        $plan = Plan::find($this->selectedPlanId);
        if (! $plan) {
            $this->errorMessage = 'Selected plan not found.';
            $this->loading = false;

            return;
        }

        $planChangeService = app(PlanChangeService::class);

        try {
            if ($this->executionType === 'scheduled') {
                // Schedule for next renewal
                $planChange = $planChangeService->scheduleChange(
                    $this->subscription,
                    $plan,
                    $this->selectedGateway,
                );

                // Send scheduled email
                Mail::to($this->subscription->user->email)
                    ->queue(new PlanChangeScheduled($planChange));

                session()->flash('success', 'Plan change scheduled for your next renewal.');
                $this->closeModal();
                $this->dispatch('plan-change-scheduled');
            } else {
                // Immediate change
                $gateway = PaymentGateway::from($this->selectedGateway);
                $result = $planChangeService->initiateImmediateChange(
                    $this->subscription,
                    $plan,
                    $gateway,
                );

                if ($result['requires_payment']) {
                    // Redirect to payment
                    $this->redirect($result['checkout_url'], navigate: false);
                } else {
                    // No payment required - change applied immediately
                    session()->flash('success', 'Your plan has been changed successfully!');
                    $this->closeModal();
                    $this->dispatch('plan-changed');
                }
            }
        } catch (Throwable $e) {
            $this->errorMessage = 'Failed to process plan change. Please try again.';
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.change-plan-modal');
    }
}

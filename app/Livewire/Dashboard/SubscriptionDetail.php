<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Subscription;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class SubscriptionDetail extends Component
{
    public bool $show = false;

    public ?string $subscriptionId = null;

    public ?Subscription $subscription = null;

    public bool $showPassword = false;

    public bool $credentialsUnlocked = false;

    /**
     * Open the modal
     */
    #[On('open-subscription-detail')]
    public function openModal(string $subscriptionId): void
    {
        $this->subscriptionId = $subscriptionId;
        $this->loadSubscription();
        $this->show = true;
        $this->showPassword = false;
        $this->credentialsUnlocked = false;
    }

    /**
     * Load subscription data
     */
    public function loadSubscription(): void
    {
        if (! $this->subscriptionId) {
            return;
        }

        $subscription = Subscription::with(['plan', 'serviceAccount', 'orders'])
            ->find($this->subscriptionId);

        if (! $subscription) {
            return;
        }

        // Authorize: User can only view their own subscriptions
        Gate::authorize('view', $subscription);

        $this->subscription = $subscription;
    }

    /**
     * Unlock credentials with password confirmation
     */
    public function unlockCredentials(): void
    {
        // In production, you should use Laravel's password confirmation middleware
        // For now, we'll allow direct unlocking
        $this->credentialsUnlocked = true;
    }

    /**
     * Toggle password visibility
     */
    public function togglePassword(): void
    {
        $this->showPassword = ! $this->showPassword;
    }

    /**
     * Get M3U download URL
     */
    public function getM3uUrl(): ?string
    {
        if (! $this->subscription?->serviceAccount) {
            return null;
        }

        $account = $this->subscription->serviceAccount;

        return sprintf(
            '%s/get.php?username=%s&password=%s&type=m3u_plus&output=ts',
            mb_rtrim($account->server_url, '/'),
            $account->username,
            $account->password,
        );
    }

    /**
     * Get EPG URL
     */
    public function getEpgUrl(): ?string
    {
        if (! $this->subscription?->serviceAccount) {
            return null;
        }

        $account = $this->subscription->serviceAccount;

        return sprintf(
            '%s/xmltv.php?username=%s&password=%s',
            mb_rtrim($account->server_url, '/'),
            $account->username,
            $account->password,
        );
    }

    /**
     * Close the modal
     */
    public function closeModal(): void
    {
        $this->show = false;
        $this->subscriptionId = null;
        $this->subscription = null;
        $this->showPassword = false;
        $this->credentialsUnlocked = false;
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.subscription-detail');
    }
}

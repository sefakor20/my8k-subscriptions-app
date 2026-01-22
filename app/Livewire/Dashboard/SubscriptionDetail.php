<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\QrCodeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SubscriptionDetail extends Component
{
    public bool $show = false;

    public ?string $subscriptionId = null;

    public ?Subscription $subscription = null;

    public bool $showPassword = false;

    public bool $credentialsUnlocked = false;

    public bool $showAutoRenewConfirm = false;

    public bool $showM3uQrCode = false;

    public bool $showEpgQrCode = false;

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
        $this->showM3uQrCode = false;
        $this->showEpgQrCode = false;
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
     * Toggle M3U QR code visibility
     */
    public function toggleM3uQrCode(): void
    {
        $this->showM3uQrCode = ! $this->showM3uQrCode;
    }

    /**
     * Toggle EPG QR code visibility
     */
    public function toggleEpgQrCode(): void
    {
        $this->showEpgQrCode = ! $this->showEpgQrCode;
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
     * Get M3U QR code SVG
     */
    #[Computed]
    public function m3uQrCodeSvg(): ?string
    {
        $url = $this->getM3uUrl();

        if (! $url) {
            return null;
        }

        return app(QrCodeService::class)->generateSvg($url);
    }

    /**
     * Get EPG QR code SVG
     */
    #[Computed]
    public function epgQrCodeSvg(): ?string
    {
        $url = $this->getEpgUrl();

        if (! $url) {
            return null;
        }

        return app(QrCodeService::class)->generateSvg($url);
    }

    /**
     * Open the change plan modal
     */
    public function openChangePlanModal(): void
    {
        if ($this->subscriptionId) {
            $this->closeModal();
            $this->dispatch('open-change-plan-modal', subscriptionId: $this->subscriptionId);
        }
    }

    /**
     * Toggle auto-renewal status
     */
    public function toggleAutoRenewal(): void
    {
        if (! $this->subscription) {
            return;
        }

        Gate::authorize('toggleAutoRenew', $this->subscription);

        $this->subscription->update([
            'auto_renew' => ! $this->subscription->auto_renew,
        ]);

        $this->subscription->refresh();
        $this->showAutoRenewConfirm = false;
    }

    /**
     * Get the renewal URL for manual renewal
     */
    public function getRenewalUrl(): ?string
    {
        if (! $this->subscription?->plan) {
            return null;
        }

        return route('checkout.gateway', [
            'plan' => $this->subscription->plan->id,
            'renew' => $this->subscription->id,
        ]);
    }

    /**
     * Check if subscription can be renewed
     */
    public function canRenew(): bool
    {
        if (! $this->subscription) {
            return false;
        }

        // Can renew if expired or expiring within 7 days
        return $this->subscription->status === SubscriptionStatus::Expired
            || ($this->subscription->status === SubscriptionStatus::Active
                && $this->subscription->daysUntilExpiry() <= 7);
    }

    /**
     * Check if subscription has stored payment method
     */
    public function hasStoredPaymentMethod(): bool
    {
        if (! $this->subscription) {
            return false;
        }

        $lastOrder = $this->subscription->orders()
            ->whereNotNull('paid_at')
            ->latest()
            ->first();

        if (! $lastOrder) {
            return false;
        }

        $payload = $lastOrder->webhook_payload ?? [];

        // Check for Paystack authorization
        if (isset($payload['data']['authorization']['authorization_code'])) {
            return true;
        }

        // Check for Stripe customer
        if (isset($payload['data']['object']['customer'])) {
            return true;
        }

        return false;
    }

    /**
     * Get payment method display info
     */
    public function getPaymentMethodInfo(): ?array
    {
        if (! $this->subscription) {
            return null;
        }

        $lastOrder = $this->subscription->orders()
            ->whereNotNull('paid_at')
            ->latest()
            ->first();

        if (! $lastOrder) {
            return null;
        }

        $payload = $lastOrder->webhook_payload ?? [];

        // Paystack card info
        if (isset($payload['data']['authorization'])) {
            $auth = $payload['data']['authorization'];

            return [
                'type' => $auth['card_type'] ?? 'Card',
                'last4' => $auth['last4'] ?? '****',
                'gateway' => 'Paystack',
            ];
        }

        // Stripe card info
        if (isset($payload['data']['object']['payment_method_details']['card'])) {
            $card = $payload['data']['object']['payment_method_details']['card'];

            return [
                'type' => ucfirst($card['brand'] ?? 'Card'),
                'last4' => $card['last4'] ?? '****',
                'gateway' => 'Stripe',
            ];
        }

        return null;
    }

    /**
     * Get activity timeline for subscription
     */
    #[Computed]
    public function activityTimeline(): Collection
    {
        if (! $this->subscription) {
            return collect();
        }

        $events = collect();

        // Add subscription created event
        $events->push([
            'type' => 'created',
            'description' => 'Subscription created',
            'date' => $this->subscription->created_at,
            'icon' => 'plus-circle',
            'color' => 'blue',
        ]);

        // Add order/payment events
        foreach ($this->subscription->orders as $order) {
            if ($order->paid_at) {
                $isRenewal = $order->created_at->gt($this->subscription->created_at->addHour());
                $events->push([
                    'type' => 'payment',
                    'description' => $isRenewal
                        ? 'Renewal payment completed (' . $order->formattedAmount() . ')'
                        : 'Payment completed (' . $order->formattedAmount() . ')',
                    'date' => $order->paid_at,
                    'icon' => 'credit-card',
                    'color' => 'green',
                ]);
            }

            if ($order->provisioned_at) {
                $events->push([
                    'type' => 'provisioned',
                    'description' => 'Service provisioned',
                    'date' => $order->provisioned_at,
                    'icon' => 'check-circle',
                    'color' => 'green',
                ]);
            }
        }

        // Add plan changes
        foreach ($this->subscription->planChanges as $change) {
            $events->push([
                'type' => 'plan_change',
                'description' => 'Plan changed to ' . ($change->toPlan?->name ?? 'Unknown'),
                'date' => $change->created_at,
                'icon' => 'arrows-right-left',
                'color' => 'purple',
            ]);
        }

        // Add last renewal if available
        if ($this->subscription->last_renewal_at) {
            $events->push([
                'type' => 'renewed',
                'description' => 'Subscription renewed',
                'date' => $this->subscription->last_renewal_at,
                'icon' => 'arrow-path',
                'color' => 'green',
            ]);
        }

        // Add status changes (cancelled, suspended)
        if ($this->subscription->cancelled_at) {
            $events->push([
                'type' => 'cancelled',
                'description' => 'Subscription cancelled',
                'date' => $this->subscription->cancelled_at,
                'icon' => 'x-circle',
                'color' => 'red',
            ]);
        }

        if ($this->subscription->suspended_at) {
            $events->push([
                'type' => 'suspended',
                'description' => 'Subscription suspended' . ($this->subscription->suspension_reason ? ': ' . $this->subscription->suspension_reason : ''),
                'date' => $this->subscription->suspended_at,
                'icon' => 'pause-circle',
                'color' => 'amber',
            ]);
        }

        // Sort by date descending (most recent first)
        return $events->sortByDesc('date')->values()->take(10);
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
        $this->showAutoRenewConfirm = false;
        $this->showM3uQrCode = false;
        $this->showEpgQrCode = false;
        unset($this->activityTimeline);
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.subscription-detail');
    }
}

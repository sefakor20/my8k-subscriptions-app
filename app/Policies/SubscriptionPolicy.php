<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admins can view all subscriptions, users can view their own
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Subscription $subscription): bool
    {
        // Admins can view any subscription, users can only view their own
        return $user->isAdmin() || $user->id === $subscription->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Subscriptions are created via WooCommerce webhooks, not directly by users
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Subscription $subscription): bool
    {
        // Only admins can update subscriptions
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Subscription $subscription): bool
    {
        // Only admins can delete subscriptions
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Subscription $subscription): bool
    {
        // Only admins can restore subscriptions
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Subscription $subscription): bool
    {
        // Only admins can force delete subscriptions
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can manually provision a subscription.
     */
    public function provision(User $user, Subscription $subscription): bool
    {
        // Only admins can manually trigger provisioning
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can suspend a subscription.
     */
    public function suspend(User $user, Subscription $subscription): bool
    {
        // Only admins can suspend subscriptions
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can reactivate a subscription.
     */
    public function reactivate(User $user, Subscription $subscription): bool
    {
        // Only admins can reactivate subscriptions
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can extend a subscription's expiry.
     */
    public function extend(User $user, Subscription $subscription): bool
    {
        // Only admins can extend subscription expiry
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view credentials for a subscription.
     */
    public function viewCredentials(User $user, Subscription $subscription): bool
    {
        // Users can view credentials for their own active subscriptions
        // Admins can view credentials for any subscription
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $subscription->user_id
               && $subscription->status === \App\Enums\SubscriptionStatus::Active
               && $subscription->serviceAccount !== null;
    }

    /**
     * Determine whether the user can toggle auto-renewal for a subscription.
     */
    public function toggleAutoRenew(User $user, Subscription $subscription): bool
    {
        // Users can toggle auto-renewal for their own active subscriptions
        // Admins can toggle for any subscription
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $subscription->user_id
               && $subscription->status === \App\Enums\SubscriptionStatus::Active;
    }
}

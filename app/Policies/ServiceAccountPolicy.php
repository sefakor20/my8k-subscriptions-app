<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ServiceAccount;
use App\Models\User;

class ServiceAccountPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admins can view all service accounts, users can view their own
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceAccount $serviceAccount): bool
    {
        // Admins can view any service account, users can only view their own
        return $user->isAdmin() || $user->id === $serviceAccount->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Service accounts are created via provisioning jobs, not directly by users
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceAccount $serviceAccount): bool
    {
        // Only admins can update service accounts
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceAccount $serviceAccount): bool
    {
        // Only admins can delete service accounts
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceAccount $serviceAccount): bool
    {
        // Only admins can restore service accounts
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServiceAccount $serviceAccount): bool
    {
        // Only admins can force delete service accounts
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view credentials for the service account.
     */
    public function viewCredentials(User $user, ServiceAccount $serviceAccount): bool
    {
        // Admins can view any credentials, users can only view their own
        return $user->isAdmin() || $user->id === $serviceAccount->user_id;
    }
}

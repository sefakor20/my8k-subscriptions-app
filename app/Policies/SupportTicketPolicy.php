<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Both customers and admins can view tickets
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SupportTicket $supportTicket): bool
    {
        // Admins can view all tickets, customers can only view their own
        return $user->is_admin || $supportTicket->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only authenticated customers can create tickets (not admins)
        return !$user->is_admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SupportTicket $supportTicket): bool
    {
        // Admins can update all tickets
        // Customers can add messages to their own open tickets
        if ($user->is_admin) {
            return true;
        }

        return $supportTicket->user_id === $user->id && $supportTicket->isOpen();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SupportTicket $supportTicket): bool
    {
        // Only admins can delete tickets
        return $user->is_admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SupportTicket $supportTicket): bool
    {
        // Only admins can restore tickets
        return $user->is_admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SupportTicket $supportTicket): bool
    {
        // Only admins can force delete tickets
        return $user->is_admin;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketCreated;
use App\Notifications\TicketReply;
use App\Notifications\TicketStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SupportTicketService
{
    /**
     * Create a new support ticket
     */
    public function createTicket(
        User $user,
        array $data,
        ?string $initialMessage = null,
    ): SupportTicket {
        return DB::transaction(function () use ($user, $data, $initialMessage) {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'subscription_id' => $data['subscription_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'subject' => $data['subject'],
                'category' => $data['category'],
                'priority' => $data['priority'] ?? 'normal',
                'status' => TicketStatus::Open,
            ]);

            // Create initial message if provided
            if ($initialMessage) {
                $this->addMessage($ticket, $user, $initialMessage);
            }

            // Notify admins of new ticket
            $this->notifyAdminsOfNewTicket($ticket);

            return $ticket->fresh();
        });
    }

    /**
     * Add a message to a ticket
     */
    public function addMessage(
        SupportTicket $ticket,
        User $user,
        string $message,
        bool $isInternalNote = false,
        ?array $attachments = null,
    ): SupportMessage {
        return DB::transaction(function () use ($ticket, $user, $message, $isInternalNote, $attachments) {
            $supportMessage = SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $message,
                'is_internal_note' => $isInternalNote,
                'attachments' => $attachments,
            ]);

            // Set first_response_at if this is the first admin response
            if ($user->is_admin && $ticket->first_response_at === null) {
                $ticket->update(['first_response_at' => now()]);
            }

            // Notify relevant parties (skip for internal notes)
            if (!$isInternalNote) {
                $this->notifyOfNewMessage($ticket, $supportMessage, $user);
            }

            return $supportMessage;
        });
    }

    /**
     * Update ticket status
     */
    public function updateStatus(
        SupportTicket $ticket,
        TicketStatus $newStatus,
        ?User $updatedBy = null,
    ): SupportTicket {
        $oldStatus = $ticket->status;

        $updates = ['status' => $newStatus];

        // Set timestamps based on status
        if ($newStatus === TicketStatus::Resolved && $ticket->resolved_at === null) {
            $updates['resolved_at'] = now();
        }

        if ($newStatus === TicketStatus::Closed && $ticket->closed_at === null) {
            $updates['closed_at'] = now();
        }

        $ticket->update($updates);

        // Notify customer of status change
        if ($oldStatus !== $newStatus) {
            $ticket->user->notify(new TicketStatusChanged($ticket, $oldStatus, $newStatus));
        }

        return $ticket->fresh();
    }

    /**
     * Assign ticket to an admin
     */
    public function assignTicket(
        SupportTicket $ticket,
        User $admin,
        ?User $assignedBy = null,
    ): SupportTicket {
        $ticket->update(['assigned_to' => $admin->id]);

        // Notify the assigned admin
        $admin->notify(new TicketAssigned($ticket, $assignedBy));

        return $ticket->fresh();
    }

    /**
     * Unassign ticket
     */
    public function unassignTicket(SupportTicket $ticket): SupportTicket
    {
        $ticket->update(['assigned_to' => null]);

        return $ticket->fresh();
    }

    /**
     * Get ticket statistics for admin dashboard
     */
    public function getStatistics(): array
    {
        return [
            'total_open' => SupportTicket::open()->count(),
            'total_closed' => SupportTicket::closed()->count(),
            'unassigned' => SupportTicket::unassigned()->open()->count(),
            'needs_first_response' => SupportTicket::open()
                ->whereNull('first_response_at')
                ->count(),
            'by_category' => SupportTicket::open()
                ->select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category'),
            'by_priority' => SupportTicket::open()
                ->select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority'),
        ];
    }

    /**
     * Notify admins of a new ticket
     */
    private function notifyAdminsOfNewTicket(SupportTicket $ticket): void
    {
        $admins = User::where('is_admin', true)->get();

        Notification::send($admins, new TicketCreated($ticket));
    }

    /**
     * Notify relevant parties of a new message
     */
    private function notifyOfNewMessage(
        SupportTicket $ticket,
        SupportMessage $message,
        User $sender,
    ): void {
        // If customer sent message, notify assigned admin or all admins
        if (!$sender->is_admin) {
            if ($ticket->isAssigned()) {
                $ticket->assignedAdmin->notify(new TicketReply($ticket, $message));
            } else {
                $admins = User::where('is_admin', true)->get();
                Notification::send($admins, new TicketReply($ticket, $message));
            }
        } else {
            // If admin sent message, notify customer
            $ticket->user->notify(new TicketReply($ticket, $message));
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public SupportTicket $ticket,
        public ?User $assignedBy = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject('Ticket Assigned to You: ' . $this->ticket->subject)
            ->greeting('Hello!')
            ->line('A support ticket has been assigned to you.')
            ->line('**Ticket #:** ' . $this->ticket->id)
            ->line('**Subject:** ' . $this->ticket->subject)
            ->line('**Category:** ' . $this->ticket->category->label())
            ->line('**Priority:** ' . $this->ticket->priority->label())
            ->line('**Customer:** ' . $this->ticket->user->name . ' (' . $this->ticket->user->email . ')');

        if ($this->assignedBy) {
            $message->line('**Assigned By:** ' . $this->assignedBy->name);
        }

        return $message
            ->action('View Ticket', route('admin.support.tickets'))
            ->line('Please review and respond to this ticket.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'category' => $this->ticket->category->value,
            'priority' => $this->ticket->priority->value,
            'customer_name' => $this->ticket->user->name,
            'assigned_by' => $this->assignedBy?->name,
        ];
    }
}

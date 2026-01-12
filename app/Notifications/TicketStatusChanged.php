<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public SupportTicket $ticket,
        public TicketStatus $oldStatus,
        public TicketStatus $newStatus,
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
            ->subject('Ticket Status Updated: ' . $this->ticket->subject)
            ->greeting('Hello!')
            ->line('The status of your support ticket has been updated.')
            ->line('**Ticket #:** ' . $this->ticket->id)
            ->line('**Subject:** ' . $this->ticket->subject)
            ->line('**Previous Status:** ' . $this->oldStatus->label())
            ->line('**New Status:** ' . $this->newStatus->label());

        if ($this->newStatus === TicketStatus::Resolved) {
            $message->line('Your ticket has been marked as resolved. If you need further assistance, please reply to this ticket.');
        } elseif ($this->newStatus === TicketStatus::Closed) {
            $message->line('Your ticket has been closed. If you need further assistance, please create a new ticket.');
        } elseif ($this->newStatus === TicketStatus::WaitingCustomer) {
            $message->line('We are waiting for your response. Please provide the requested information to continue.');
        }

        return $message
            ->action('View Ticket', route('support.my-tickets'))
            ->line('Thank you for your patience.');
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
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
        ];
    }
}

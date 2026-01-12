<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public SupportTicket $ticket,
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
        return (new MailMessage())
            ->subject('New Support Ticket Created: ' . $this->ticket->subject)
            ->greeting('Hello!')
            ->line('A new support ticket has been created.')
            ->line('**Ticket #:** ' . $this->ticket->id)
            ->line('**Subject:** ' . $this->ticket->subject)
            ->line('**Category:** ' . $this->ticket->category->label())
            ->line('**Priority:** ' . $this->ticket->priority->label())
            ->line('**From:** ' . $this->ticket->user->name . ' (' . $this->ticket->user->email . ')')
            ->action('View Ticket', route('admin.support.tickets'))
            ->line('Please review and respond to this ticket as soon as possible.');
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
            'user_name' => $this->ticket->user->name,
        ];
    }
}

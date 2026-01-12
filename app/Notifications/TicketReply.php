<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TicketReply extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public SupportTicket $ticket,
        public SupportMessage $message,
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
        $isAdmin = $this->message->user->is_admin ?? false;
        $subject = $isAdmin
            ? 'Support Team Replied to Your Ticket: ' . $this->ticket->subject
            : 'New Reply on Ticket: ' . $this->ticket->subject;

        $route = $isAdmin
            ? route('support.my-tickets')
            : route('admin.support.tickets');

        return (new MailMessage())
            ->subject($subject)
            ->greeting('Hello!')
            ->line('A new message has been added to your support ticket.')
            ->line('**Ticket #:** ' . $this->ticket->id)
            ->line('**Subject:** ' . $this->ticket->subject)
            ->line('**From:** ' . $this->message->user->name)
            ->line('')
            ->line('**Message:**')
            ->line(Str::limit($this->message->message, 200))
            ->action('View Full Conversation', $route)
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
            'message_id' => $this->message->id,
            'subject' => $this->ticket->subject,
            'sender_name' => $this->message->user->name,
            'message_preview' => Str::limit($this->message->message, 100),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PlanChange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanChangeConfirmed extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public PlanChange $planChange,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $actionLabel = $this->planChange->isUpgrade() ? 'Upgrade' : 'Downgrade';

        return new Envelope(
            subject: "Plan {$actionLabel} Confirmed - {$this->planChange->toPlan->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.plan-change-confirmed',
            with: [
                'planChange' => $this->planChange,
                'user' => $this->planChange->user,
                'fromPlan' => $this->planChange->fromPlan,
                'toPlan' => $this->planChange->toPlan,
                'subscription' => $this->planChange->subscription,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

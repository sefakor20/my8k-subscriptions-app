<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResellerCreditAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public float $currentBalance,
        public string $alertLevel,
        public ?int $estimatedDepletionDays = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject($this->getSubject())
            ->greeting($this->getGreeting())
            ->line($this->getMessage())
            ->line("**Current Balance:** {$this->formatBalance()} credits");

        if ($this->estimatedDepletionDays) {
            $message->line("**Estimated Depletion:** ~{$this->estimatedDepletionDays} days");
        }

        $message->action('View Credits Dashboard', route('admin.credits'))
            ->line('Please top up your reseller account to avoid service interruptions.');

        return $this->applyLevelStyling($message);
    }

    /**
     * Get alert subject based on level
     */
    private function getSubject(): string
    {
        return match ($this->alertLevel) {
            'urgent' => '🚨 URGENT: Reseller Credits Critically Low',
            'critical' => '⚠️ CRITICAL: Reseller Credits Running Low',
            'warning' => '⚠️ WARNING: Low Reseller Credits Balance',
            default => 'Reseller Credits Alert',
        };
    }

    /**
     * Get greeting based on level
     */
    private function getGreeting(): string
    {
        return match ($this->alertLevel) {
            'urgent' => 'Urgent Action Required!',
            'critical' => 'Critical Alert',
            'warning' => 'Low Balance Warning',
            default => 'Hello!',
        };
    }

    /**
     * Get message based on level
     */
    private function getMessage(): string
    {
        return match ($this->alertLevel) {
            'urgent' => 'Your My8K reseller credit balance is **critically low**. Service provisioning may fail at any moment. **Immediate action is required.**',
            'critical' => 'Your My8K reseller credit balance has reached a **critical level**. You should top up your account as soon as possible to prevent service disruptions.',
            'warning' => 'Your My8K reseller credit balance is running low. Please plan to top up your account soon to ensure uninterrupted service.',
            default => 'Your reseller credit balance requires attention.',
        };
    }

    /**
     * Format balance for display
     */
    private function formatBalance(): string
    {
        return number_format($this->currentBalance, 0);
    }

    /**
     * Apply level-specific styling
     */
    private function applyLevelStyling(MailMessage $message): MailMessage
    {
        return match ($this->alertLevel) {
            'urgent' => $message->error(),
            'critical' => $message->error(),
            'warning' => $message,
            default => $message,
        };
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'alert_level' => $this->alertLevel,
            'current_balance' => $this->currentBalance,
            'estimated_depletion_days' => $this->estimatedDepletionDays,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Enums\NotificationLogStatus;
use App\Mail\AccountCredentialsReady;
use App\Mail\InvoiceGenerated;
use App\Mail\PaymentFailureReminder;
use App\Mail\PlanChangeConfirmed;
use App\Mail\PlanChangeScheduled;
use App\Mail\ProvisioningFailed;
use App\Mail\SubscriptionExpiringSoon;
use App\Mail\SubscriptionRenewed;
use App\Mail\SubscriptionRenewalFailed;
use App\Mail\SuspensionWarning;
use App\Mail\WelcomeNewCustomer;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationService
{
    /**
     * Map of Mailable classes to their notification categories.
     *
     * @var array<class-string<Mailable>, NotificationCategory>
     */
    private const array MAILABLE_CATEGORIES = [
        // Critical (always sent)
        PaymentFailureReminder::class => NotificationCategory::Critical,
        SuspensionWarning::class => NotificationCategory::Critical,
        SubscriptionRenewalFailed::class => NotificationCategory::Critical,
        ProvisioningFailed::class => NotificationCategory::Critical,
        AccountCredentialsReady::class => NotificationCategory::Critical,
        WelcomeNewCustomer::class => NotificationCategory::Critical,

        // Renewal Reminders
        SubscriptionExpiringSoon::class => NotificationCategory::RenewalReminders,
        SubscriptionRenewed::class => NotificationCategory::RenewalReminders,

        // Plan Changes
        PlanChangeScheduled::class => NotificationCategory::PlanChanges,
        PlanChangeConfirmed::class => NotificationCategory::PlanChanges,

        // Invoices
        InvoiceGenerated::class => NotificationCategory::Invoices,
    ];

    /**
     * Send an email immediately with preference check and logging.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function sendMail(User $user, Mailable $mailable, array $metadata = []): NotificationLog
    {
        $mailableClass = $mailable::class;
        $category = $this->getCategoryForMailable($mailableClass);
        $subject = $this->extractSubject($mailable);

        if (! $this->shouldSendNotification($user, $category)) {
            return $this->logNotification(
                user: $user,
                notificationType: $mailableClass,
                category: $category,
                channel: 'mail',
                subject: $subject,
                metadata: $metadata,
                status: NotificationLogStatus::Blocked,
            );
        }

        try {
            Mail::to($user->email)->send($mailable);

            return $this->logNotification(
                user: $user,
                notificationType: $mailableClass,
                category: $category,
                channel: 'mail',
                subject: $subject,
                metadata: $metadata,
                status: NotificationLogStatus::Sent,
            );
        } catch (Throwable $e) {
            Log::error('Failed to send notification email', [
                'user_id' => $user->id,
                'mailable' => $mailableClass,
                'error' => $e->getMessage(),
            ]);

            return $this->logNotification(
                user: $user,
                notificationType: $mailableClass,
                category: $category,
                channel: 'mail',
                subject: $subject,
                metadata: $metadata,
                status: NotificationLogStatus::Failed,
                failureReason: $e->getMessage(),
            );
        }
    }

    /**
     * Queue an email with preference check and logging.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function queueMail(User $user, Mailable $mailable, array $metadata = []): NotificationLog
    {
        $mailableClass = $mailable::class;
        $category = $this->getCategoryForMailable($mailableClass);
        $subject = $this->extractSubject($mailable);

        if (! $this->shouldSendNotification($user, $category)) {
            return $this->logNotification(
                user: $user,
                notificationType: $mailableClass,
                category: $category,
                channel: 'mail',
                subject: $subject,
                metadata: $metadata,
                status: NotificationLogStatus::Blocked,
            );
        }

        try {
            Mail::to($user->email)->queue($mailable);

            return $this->logNotification(
                user: $user,
                notificationType: $mailableClass,
                category: $category,
                channel: 'mail',
                subject: $subject,
                metadata: $metadata,
                status: NotificationLogStatus::Sent,
            );
        } catch (Throwable $e) {
            Log::error('Failed to queue notification email', [
                'user_id' => $user->id,
                'mailable' => $mailableClass,
                'error' => $e->getMessage(),
            ]);

            return $this->logNotification(
                user: $user,
                notificationType: $mailableClass,
                category: $category,
                channel: 'mail',
                subject: $subject,
                metadata: $metadata,
                status: NotificationLogStatus::Failed,
                failureReason: $e->getMessage(),
            );
        }
    }

    /**
     * Check if a notification should be sent based on user preferences.
     */
    public function shouldSendNotification(User $user, NotificationCategory $category, string $channel = 'mail'): bool
    {
        return $user->hasNotificationEnabled($category, $channel);
    }

    /**
     * Get the notification category for a mailable class.
     *
     * @param  class-string<Mailable>  $mailableClass
     */
    public function getCategoryForMailable(string $mailableClass): NotificationCategory
    {
        return self::MAILABLE_CATEGORIES[$mailableClass] ?? NotificationCategory::Critical;
    }

    /**
     * Initialize default notification preferences for a user.
     */
    public function initializeUserPreferences(User $user): void
    {
        foreach (NotificationCategory::configurable() as $category) {
            NotificationPreference::firstOrCreate([
                'user_id' => $user->id,
                'category' => $category,
                'channel' => 'mail',
            ], [
                'is_enabled' => true,
            ]);
        }
    }

    /**
     * Update a user's notification preference.
     */
    public function updatePreference(
        User $user,
        NotificationCategory $category,
        bool $enabled,
        string $channel = 'mail',
    ): NotificationPreference {
        return NotificationPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'category' => $category,
                'channel' => $channel,
            ],
            [
                'is_enabled' => $enabled,
            ],
        );
    }

    /**
     * Get all user preferences for display.
     *
     * @return array<int, array{category: NotificationCategory, is_enabled: bool}>
     */
    public function getUserPreferences(User $user): array
    {
        $existingPreferences = $user->notificationPreferences()
            ->where('channel', 'mail')
            ->get()
            ->keyBy(fn($p) => $p->category->value);

        $preferences = [];
        foreach (NotificationCategory::configurable() as $category) {
            $existing = $existingPreferences->get($category->value);
            $preferences[] = [
                'category' => $category,
                'is_enabled' => $existing?->is_enabled ?? true,
            ];
        }

        return $preferences;
    }

    /**
     * Log a notification to the database.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function logNotification(
        User $user,
        string $notificationType,
        NotificationCategory $category,
        string $channel,
        ?string $subject,
        array $metadata,
        NotificationLogStatus $status,
        ?string $failureReason = null,
    ): NotificationLog {
        return NotificationLog::create([
            'user_id' => $user->id,
            'notification_type' => $notificationType,
            'category' => $category,
            'channel' => $channel,
            'subject' => $subject,
            'metadata' => $metadata,
            'status' => $status,
            'failure_reason' => $failureReason,
            'sent_at' => $status === NotificationLogStatus::Sent ? now() : null,
        ]);
    }

    /**
     * Extract the subject from a mailable.
     */
    private function extractSubject(Mailable $mailable): ?string
    {
        try {
            $envelope = $mailable->envelope();

            return $envelope->subject;
        } catch (Throwable) {
            return null;
        }
    }
}

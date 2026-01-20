<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationCategory: string
{
    case Marketing = 'marketing';
    case RenewalReminders = 'renewal_reminders';
    case PlanChanges = 'plan_changes';
    case Invoices = 'invoices';
    case SupportUpdates = 'support_updates';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Marketing => 'Marketing & Promotions',
            self::RenewalReminders => 'Renewal Reminders',
            self::PlanChanges => 'Plan Change Notifications',
            self::Invoices => 'Invoice Notifications',
            self::SupportUpdates => 'Support Ticket Updates',
            self::Critical => 'Critical Notifications',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Marketing => 'Promotional offers, new features, and product updates',
            self::RenewalReminders => 'Reminders about upcoming subscription renewals',
            self::PlanChanges => 'Notifications about plan upgrades, downgrades, and scheduled changes',
            self::Invoices => 'Invoice generation and payment confirmations',
            self::SupportUpdates => 'Updates on your support tickets',
            self::Critical => 'Payment failures, suspension warnings, and account security alerts',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Marketing => 'megaphone',
            self::RenewalReminders => 'arrow-path',
            self::PlanChanges => 'arrows-right-left',
            self::Invoices => 'document-text',
            self::SupportUpdates => 'chat-bubble-left-right',
            self::Critical => 'exclamation-triangle',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Marketing => 'blue',
            self::RenewalReminders => 'yellow',
            self::PlanChanges => 'purple',
            self::Invoices => 'green',
            self::SupportUpdates => 'cyan',
            self::Critical => 'red',
        };
    }

    public function isOptional(): bool
    {
        return $this !== self::Critical;
    }

    /**
     * Get all configurable categories (those that can be toggled by users).
     *
     * @return array<self>
     */
    public static function configurable(): array
    {
        return array_filter(self::cases(), fn(self $case): bool => $case->isOptional());
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketCategory: string
{
    case Technical = 'technical';
    case Billing = 'billing';
    case Account = 'account';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Technical => 'Technical Support',
            self::Billing => 'Billing & Payments',
            self::Account => 'Account Management',
            self::General => 'General Inquiry',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Technical => 'wrench-screwdriver',
            self::Billing => 'credit-card',
            self::Account => 'user-circle',
            self::General => 'chat-bubble-left-right',
        };
    }
}

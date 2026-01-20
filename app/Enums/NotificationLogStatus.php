<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationLogStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::Blocked => 'Blocked',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Sent => 'green',
            self::Failed => 'red',
            self::Blocked => 'zinc',
        };
    }
}

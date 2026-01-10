<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Active => 'green',
            self::Suspended => 'orange',
            self::Expired => 'gray',
            self::Cancelled => 'red',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function canBeRenewed(): bool
    {
        return in_array($this, [self::Active, self::Expired]);
    }
}

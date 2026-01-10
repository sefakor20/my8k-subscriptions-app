<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceAccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Suspended => 'orange',
            self::Expired => 'gray',
        };
    }

    public function isUsable(): bool
    {
        return $this === self::Active;
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum PlanChangeType: string
{
    case Upgrade = 'upgrade';
    case Downgrade = 'downgrade';

    public function label(): string
    {
        return match ($this) {
            self::Upgrade => 'Upgrade',
            self::Downgrade => 'Downgrade',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Upgrade => 'green',
            self::Downgrade => 'yellow',
        };
    }

    public function isUpgrade(): bool
    {
        return $this === self::Upgrade;
    }

    public function isDowngrade(): bool
    {
        return $this === self::Downgrade;
    }
}

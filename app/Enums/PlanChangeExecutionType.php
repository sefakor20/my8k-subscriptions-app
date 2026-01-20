<?php

declare(strict_types=1);

namespace App\Enums;

enum PlanChangeExecutionType: string
{
    case Immediate = 'immediate';
    case Scheduled = 'scheduled';

    public function label(): string
    {
        return match ($this) {
            self::Immediate => 'Immediate',
            self::Scheduled => 'At Next Renewal',
        };
    }

    public function isImmediate(): bool
    {
        return $this === self::Immediate;
    }

    public function isScheduled(): bool
    {
        return $this === self::Scheduled;
    }
}

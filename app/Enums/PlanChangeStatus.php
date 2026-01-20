<?php

declare(strict_types=1);

namespace App\Enums;

enum PlanChangeStatus: string
{
    case Pending = 'pending';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Scheduled => 'Scheduled',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Scheduled => 'blue',
            self::Completed => 'green',
            self::Failed => 'red',
            self::Cancelled => 'gray',
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isScheduled(): bool
    {
        return $this === self::Scheduled;
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::Pending, self::Scheduled], true);
    }
}

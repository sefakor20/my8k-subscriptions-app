<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentTransactionStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Success => 'Success',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
            self::Abandoned => 'Abandoned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Success => 'green',
            self::Failed => 'red',
            self::Refunded => 'gray',
            self::Abandoned => 'zinc',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::Success;
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Success, self::Failed, self::Refunded, self::Abandoned], true);
    }
}

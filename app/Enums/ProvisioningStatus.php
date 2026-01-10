<?php

declare(strict_types=1);

namespace App\Enums;

enum ProvisioningStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Retrying = 'retrying';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Success => 'Success',
            self::Failed => 'Failed',
            self::Retrying => 'Retrying',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Success => 'green',
            self::Failed => 'red',
            self::Retrying => 'orange',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::Success;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }

    public function canRetry(): bool
    {
        return in_array($this, [self::Failed, self::Retrying]);
    }
}

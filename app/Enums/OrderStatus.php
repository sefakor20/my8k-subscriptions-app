<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case PendingProvisioning = 'pending_provisioning';
    case Provisioned = 'provisioned';
    case ProvisioningFailed = 'provisioning_failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PendingProvisioning => 'Pending Provisioning',
            self::Provisioned => 'Provisioned',
            self::ProvisioningFailed => 'Provisioning Failed',
            self::Refunded => 'Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingProvisioning => 'yellow',
            self::Provisioned => 'green',
            self::ProvisioningFailed => 'red',
            self::Refunded => 'gray',
        };
    }

    public function needsProvisioning(): bool
    {
        return $this === self::PendingProvisioning;
    }

    public function isProvisioned(): bool
    {
        return $this === self::Provisioned;
    }
}

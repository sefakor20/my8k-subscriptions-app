<?php

declare(strict_types=1);

namespace App\Enums;

enum BillingInterval: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Yearly => 'Yearly',
        };
    }

    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
            self::Yearly => 12,
        };
    }

    public function days(): int
    {
        return $this->months() * 30;
    }

    public function description(): string
    {
        return match ($this) {
            self::Monthly => 'Billed every month',
            self::Quarterly => 'Billed every 3 months',
            self::Yearly => 'Billed annually',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum CouponDiscountType: string
{
    case Percentage = 'percentage';
    case FixedAmount = 'fixed_amount';
    case TrialExtension = 'trial_extension';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage Discount',
            self::FixedAmount => 'Fixed Amount',
            self::TrialExtension => 'Trial Extension',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Percentage => 'Discount as a percentage of the order total',
            self::FixedAmount => 'Fixed amount off the order total',
            self::TrialExtension => 'Extend the trial period by additional days',
        };
    }

    public function requiresCurrency(): bool
    {
        return $this === self::FixedAmount;
    }

    public function requiresTrialDays(): bool
    {
        return $this === self::TrialExtension;
    }
}

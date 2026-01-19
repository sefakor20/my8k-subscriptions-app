<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Paid = 'paid';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued',
            self::Paid => 'Paid',
            self::Void => 'Void',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'yellow',
            self::Issued => 'blue',
            self::Paid => 'green',
            self::Void => 'gray',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    public function isVoid(): bool
    {
        return $this === self::Void;
    }

    public function canBeVoided(): bool
    {
        return $this !== self::Void;
    }
}

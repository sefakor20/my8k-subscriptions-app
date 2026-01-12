<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Normal => 'Normal',
            self::High => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'zinc',
            self::Normal => 'blue',
            self::High => 'orange',
            self::Urgent => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Low => 'arrow-down',
            self::Normal => 'minus',
            self::High => 'arrow-up',
            self::Urgent => 'exclamation-triangle',
        };
    }
}

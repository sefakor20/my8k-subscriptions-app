<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingCustomer = 'waiting_customer';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::WaitingCustomer => 'Waiting for Customer',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'blue',
            self::InProgress => 'yellow',
            self::WaitingCustomer => 'orange',
            self::Resolved => 'green',
            self::Closed => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Open => 'envelope-open',
            self::InProgress => 'arrow-path',
            self::WaitingCustomer => 'clock',
            self::Resolved => 'check-circle',
            self::Closed => 'archive-box',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::InProgress, self::WaitingCustomer]);
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Resolved, self::Closed]);
    }
}

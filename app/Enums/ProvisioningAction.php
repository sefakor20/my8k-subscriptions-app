<?php

declare(strict_types=1);

namespace App\Enums;

enum ProvisioningAction: string
{
    case Create = 'create';
    case Extend = 'extend';
    case Suspend = 'suspend';
    case Reactivate = 'reactivate';
    case Query = 'query';

    public function label(): string
    {
        return match ($this) {
            self::Create => 'Create Account',
            self::Extend => 'Extend Account',
            self::Suspend => 'Suspend Account',
            self::Reactivate => 'Reactivate Account',
            self::Query => 'Query Account',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Create => 'plus-circle',
            self::Extend => 'arrow-path',
            self::Suspend => 'pause-circle',
            self::Reactivate => 'play-circle',
            self::Query => 'magnifying-glass',
        };
    }
}

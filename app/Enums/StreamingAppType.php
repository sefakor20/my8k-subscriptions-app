<?php

declare(strict_types=1);

namespace App\Enums;

enum StreamingAppType: string
{
    case MAG = 'mag';
    case M3U = 'm3u';
    case Enigma2 = 'enigma2';

    public function label(): string
    {
        return match ($this) {
            self::MAG => 'MAG',
            self::M3U => 'M3U',
            self::Enigma2 => 'Enigma2',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MAG => 'For MAG boxes and Stalker Portal',
            self::M3U => 'For M3U playlist players',
            self::Enigma2 => 'For Linux boxes (Enigma1/Enigma2)',
        };
    }
}

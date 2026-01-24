<?php

declare(strict_types=1);

namespace App\Enums;

enum StreamingAppPlatform: string
{
    case Android = 'android';
    case Windows = 'windows';
    case SmartTV = 'smart_tv';
    case LinuxBox = 'linux_box';
    case iOS = 'ios';
    case MacOS = 'macos';
    case FireTV = 'fire_tv';

    public function label(): string
    {
        return match ($this) {
            self::Android => 'Android',
            self::Windows => 'Windows',
            self::SmartTV => 'Smart TV',
            self::LinuxBox => 'Linux Box',
            self::iOS => 'iOS',
            self::MacOS => 'macOS',
            self::FireTV => 'Fire TV',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Android => 'device-phone-mobile',
            self::Windows => 'computer-desktop',
            self::SmartTV => 'tv',
            self::LinuxBox => 'server',
            self::iOS => 'device-phone-mobile',
            self::MacOS => 'computer-desktop',
            self::FireTV => 'tv',
        };
    }
}

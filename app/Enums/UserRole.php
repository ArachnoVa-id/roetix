<?php

namespace App\Enums;

use App\Enums\Traits\BaseEnumTrait;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel, HasColor
{
    use BaseEnumTrait;

    // Get by version
    public static function getByVersion(string $version, EnumVersionType $mode = EnumVersionType::DEFAULT): array|string
    {
        return match ($version) {
            'v1' => match ($mode) {
                EnumVersionType::ARRAY => [
                    'user',
                    'admin',
                    'vendor',
                    'event-organizer',
                ],
                EnumVersionType::DEFAULT => 'user',
                default => throw new \InvalidArgumentException("Mode {$mode} not supported."),
            },
            'v2' => match ($mode) {
                EnumVersionType::ARRAY => [
                    'user',
                    'admin',
                    'vendor',
                    'event-organizer',
                    'receptionist',
                ],
                EnumVersionType::DEFAULT => 'user',
                default => throw new \InvalidArgumentException("Mode {$mode} not supported."),
            },
            default => throw new \InvalidArgumentException("Version {$version} not supported."),
        };
    }

    case USER = 'user';
    case ADMIN = 'admin';
    case VENDOR = 'vendor';
    case EVENT_ORGANIZER = 'event-organizer';
    case RECEPTIONIST = 'receptionist';

    public function getLabel(): string
    {
        return match ($this) {
            self::USER => 'User',
            self::ADMIN => 'Admin',
            self::VENDOR => 'Vendor',
            self::EVENT_ORGANIZER => 'EO',
            self::RECEPTIONIST => 'Receptionist'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::USER => Color::Green,
            self::ADMIN => Color::Purple,
            self::VENDOR => Color::Blue,
            self::EVENT_ORGANIZER => Color::Yellow,
            self::RECEPTIONIST => Color::Orange
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::USER => 'heroicon-o-user',
            self::ADMIN => 'heroicon-o-check-badge',
            self::VENDOR => 'heroicon-o-building-library',
            self::EVENT_ORGANIZER => 'heroicon-o-calendar-days',
            self::RECEPTIONIST => 'heroicon-o-user-plus'
        };
    }

    public static function editableOptions()
    {
        return [
            self::USER->value => self::USER->getLabel(),
            self::ADMIN->value => self::ADMIN->getLabel(),
            self::VENDOR->value => self::VENDOR->getLabel(),
            self::EVENT_ORGANIZER->value => self::EVENT_ORGANIZER->getLabel(),
            self::RECEPTIONIST->value => self::RECEPTIONIST->getLabel()
        ];
    }
}

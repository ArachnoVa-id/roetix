<?php

namespace App\Enums;

use App\Enums\Traits\BaseEnumTrait;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel, HasColor
{
    use BaseEnumTrait;

    case USER = 'user';
    case ADMIN = 'admin';
    case VENDOR = 'vendor';
    case EVENT_ORGANIZER = 'event-organizer';

    public function getLabel(): string
    {
        return match ($this) {
            self::USER => 'User',
            self::ADMIN => 'Admin',
            self::VENDOR => 'Vendor',
            self::EVENT_ORGANIZER => 'EO'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::USER => Color::Green,
            self::ADMIN => Color::Purple,
            self::VENDOR => Color::Blue,
            self::EVENT_ORGANIZER => Color::Yellow
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::USER => 'heroicon-o-user',
            self::ADMIN => 'heroicon-o-check-badge',
            self::VENDOR => 'heroicon-o-building-library',
            self::EVENT_ORGANIZER => 'heroicon-o-calendar-days'
        };
    }

    public static function editableOptions()
    {
        return [
            self::USER->value => self::USER->getLabel(),
            self::ADMIN->value => self::ADMIN->getLabel(),
            self::VENDOR->value => self::VENDOR->getLabel(),
            self::EVENT_ORGANIZER->value => self::EVENT_ORGANIZER->getLabel(),
        ];
    }
}

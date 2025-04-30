<?php

namespace App\Enums;

use App\Enums\Traits\BaseEnumTrait;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VenueStatus: string implements HasLabel, HasColor
{
    use BaseEnumTrait;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case UNDER_MAINTENANCE = 'under_maintenance';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::UNDER_MAINTENANCE => 'Maintenance'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => Color::Green,
            self::INACTIVE => Color::Red,
            self::UNDER_MAINTENANCE => Color::Slate
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-o-check-circle',
            self::INACTIVE => 'heroicon-o-x-circle',
            self::UNDER_MAINTENANCE => 'heroicon-o-wrench'
        };
    }

    public static function editableOptions()
    {
        return [
            self::ACTIVE->value => self::ACTIVE->getLabel(),
            self::INACTIVE->value => self::INACTIVE->getLabel(),
            self::UNDER_MAINTENANCE->value => self::UNDER_MAINTENANCE->getLabel()
        ];
    }
}

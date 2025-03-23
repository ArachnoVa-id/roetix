<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VenueStatus: string implements HasLabel
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case UNDER_MAINTENANCE = 'under_maintenance';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'active',
            self::INACTIVE => 'inactive',
            self::UNDER_MAINTENANCE => 'under_maintenance'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => Color::Green,
            self::INACTIVE => Color::Red,
            self::UNDER_MAINTENANCE => Color::Gray
        };
    }

    public static function fromLabel(string $label): self
    {
        foreach (self::cases() as $case) {
            if ($case->getLabel() === $label) {
                return $case;
            }
        }

        throw new \ValueError("\"$label\" is not a valid label for enum " . self::class);
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => $case->getLabel(), self::cases());
    }
    

    public static function editableOptions()
    {
        return [
            self::ACTIVE->value => self::ACTIVE->getLabel(),
            self::INACTIVE->value => self::INACTIVE->getLabel(),
            self::UNDER_MAINTENANCE->value => self::UNDER_MAINTENANCE->getLabel()
        ];
    }

    public static function getEditableOptionsValues()
    {
        return array_keys(self::editableOptions());
    }
}

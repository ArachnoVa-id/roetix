<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
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
            self::ADMIN => Color::Red,
            self::VENDOR => Color::Gray,
            self::EVENT_ORGANIZER => Color::Yellow
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

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
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

    public static function getEditableOptionsValues()
    {
        return array_keys(self::editableOptions());
    }
}

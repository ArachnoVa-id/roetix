<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EventStatus: string implements HasLabel
{
    case PLANNED = 'planned';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PLANNED => 'Planned',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PLANNED => Color::Green,
            self::ACTIVE => Color::Red,
            self::COMPLETED => Color::Gray,
            self::CANCELLED => Color::Yellow
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
            self::PLANNED->value => self::PLANNED->getLabel(),
            self::ACTIVE->value => self::ACTIVE->getLabel(),
            self::COMPLETED->value => self::COMPLETED->getLabel(),
            self::CANCELLED->value => self::CANCELLED->getLabel(),
        ];
    }

    public static function getEditableOptionsValues()
    {
        return array_keys(self::editableOptions());
    }
}

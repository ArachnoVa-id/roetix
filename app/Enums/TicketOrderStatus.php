<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketOrderStatus: string implements HasLabel, HasColor
{
    case ENABLED = 'enabled';
    case SCANNED = 'scanned';
    case DEACTIVATED = 'deactivated';

    public function getLabel(): string
    {
        return match ($this) {
            self::ENABLED => 'Enabled',
            self::SCANNED => 'Scanned',
            self::DEACTIVATED => 'Deactivated',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ENABLED => Color::Green,
            self::SCANNED => Color::Yellow,
            self::DEACTIVATED => Color::Red,
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
            self::ENABLED->value => self::ENABLED->getLabel(),
            self::DEACTIVATED->value => self::DEACTIVATED->getLabel(),
        ];
    }

    public static function getEditableOptionsValues()
    {
        return array_keys(self::editableOptions());
    }
}

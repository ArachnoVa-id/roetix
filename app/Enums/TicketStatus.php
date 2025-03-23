<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketStatus: string implements HasLabel
{
    case AVAILABLE = 'available';
    case BOOKED = 'booked';
    case RESERVED = 'reserved';
    case IN_TRANSACTION = 'in_transaction';

    public function getLabel(): string
    {
        return match ($this) {
            self::AVAILABLE => 'available',
            self::BOOKED => 'booked',
            self::RESERVED => 'reserved',
            self::IN_TRANSACTION => 'in_transaction'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::AVAILABLE => Color::Green,
            self::BOOKED => Color::Red,
            self::RESERVED => Color::Gray,
            self::IN_TRANSACTION => Color::Yellow
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
            self::AVAILABLE->value => self::AVAILABLE->getLabel(),
            self::BOOKED->value => self::BOOKED->getLabel(),
            self::RESERVED->value => self::RESERVED->getLabel(),
            self::IN_TRANSACTION->value => self::IN_TRANSACTION->getLabel(),
        ];
    }

    public static function getEditableOptionsValues()
    {
        return array_keys(self::editableOptions());
    }
}

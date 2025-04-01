<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasLabel, HasColor
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => Color::Green,
            self::COMPLETED => Color::Red,
            self::CANCELLED => Color::Gray
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

    public static function allOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }
        return $options;
    }

    public static function editableOptions(OrderStatus $currentStatus)
    {
        switch ($currentStatus) {
            case self::PENDING:
                return [
                    self::PENDING->value => self::PENDING->getLabel(),
                    self::COMPLETED->value => self::COMPLETED->getLabel(),
                    self::CANCELLED->value => self::CANCELLED->getLabel(),
                ];
            case self::COMPLETED:
                return [
                    self::COMPLETED->value => self::COMPLETED->getLabel(),
                ];
            case self::CANCELLED:
                return [
                    self::CANCELLED->value => self::CANCELLED->getLabel(),
                ];
            default:
                return [];
        }
    }

    public static function getEditableOptionsValues()
    {
        return array_keys(self::allOptions());
    }
}

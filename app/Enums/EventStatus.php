<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EventStatus: string implements HasLabel, HasColor
{
    case PLANNED = 'planned';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

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

    public static function editableOptions(EventStatus $currentStatus)
    {
        switch ($currentStatus) {
            case self::PLANNED:
                return [
                    self::PLANNED->value => self::PLANNED->getLabel(),
                    self::ACTIVE->value => self::ACTIVE->getLabel(),
                    self::CANCELLED->value => self::CANCELLED->getLabel(),
                ];
            case self::ACTIVE:
                return [
                    self::ACTIVE->value => self::ACTIVE->getLabel(),
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

    public static function allOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }
        return $options;
    }

    public static function getFilterOptions()
    {
        return [
            self::PLANNED->getLabel() => self::PLANNED->getLabel(),
            self::ACTIVE->getLabel() => self::ACTIVE->getLabel(),
            self::COMPLETED->getLabel() => self::COMPLETED->getLabel(),
            self::CANCELLED->getLabel() => self::CANCELLED->getLabel(),
        ];
    }
}

<?php

namespace App\Enums;

use App\Enums\Traits\BaseEnumTrait;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EventStatus: string implements HasLabel, HasColor
{
    use BaseEnumTrait;

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
            self::PLANNED => Color::Yellow,
            self::ACTIVE => Color::Blue,
            self::COMPLETED => Color::Green,
            self::CANCELLED => Color::Red
        };
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

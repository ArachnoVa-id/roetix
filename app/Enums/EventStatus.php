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

    public function getIcon(): string
    {
        return match ($this) {
            self::PLANNED => 'heroicon-o-calendar',
            self::ACTIVE => 'heroicon-o-play',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::CANCELLED => 'heroicon-o-x-circle'
        };
    }

    public static function editableOptions(EventStatus $currentStatus)
    {
        $returnStatuses = [];

        $returnStatuses[$currentStatus->value] = $currentStatus->getLabel();

        switch ($currentStatus) {
            case self::PLANNED:
                $returnStatuses[self::PLANNED->value] = self::PLANNED->getLabel();
                $returnStatuses[self::ACTIVE->value] = self::ACTIVE->getLabel();
                $returnStatuses[self::CANCELLED->value] = self::CANCELLED->getLabel();
                break;

            case self::ACTIVE:
                $returnStatuses[self::ACTIVE->value] = self::ACTIVE->getLabel();
                $returnStatuses[self::COMPLETED->value] = self::COMPLETED->getLabel();
                $returnStatuses[self::CANCELLED->value] = self::CANCELLED->getLabel();
                break;

            case self::COMPLETED:
                $returnStatuses[self::COMPLETED->value] = self::COMPLETED->getLabel();
                break;

            case self::CANCELLED:
                $returnStatuses[self::CANCELLED->value] = self::CANCELLED->getLabel();
                break;
        }

        return $returnStatuses;
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

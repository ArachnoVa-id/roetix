<?php

namespace App\Enums;

use App\Enums\Traits\BaseEnumTrait;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasLabel, HasColor
{
    use BaseEnumTrait;

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

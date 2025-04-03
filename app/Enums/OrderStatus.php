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
            self::PENDING => Color::Blue,
            self::COMPLETED => Color::Green,
            self::CANCELLED => Color::Red
        };
    }

    public static function editableOptions(OrderStatus $currentStatus)
    {
        $returnStatuses = [];

        $returnStatuses[$currentStatus->value] = $currentStatus->getLabel();

        switch ($currentStatus) {
            case self::PENDING:
                $returnStatuses[self::PENDING->value] = self::PENDING->getLabel();
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
}

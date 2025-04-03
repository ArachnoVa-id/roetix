<?php

namespace App\Enums;

use App\Enums\Traits\BaseEnumTrait;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketStatus: string implements HasLabel, HasColor
{
    use BaseEnumTrait;

    case AVAILABLE = 'available';
    case BOOKED = 'booked';
    case RESERVED = 'reserved';
    case IN_TRANSACTION = 'in_transaction';

    public function getLabel(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::BOOKED => 'Booked',
            self::RESERVED => 'Reserved',
            self::IN_TRANSACTION => 'In Transaction'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::AVAILABLE => Color::Emerald,
            self::BOOKED => Color::Cyan,
            self::RESERVED => Color::Stone,
            self::IN_TRANSACTION => Color::Rose
        };
    }

    public static function editableOptions(TicketStatus $currentStatus)
    {
        $returnStatuses = [
            self::AVAILABLE->value => self::AVAILABLE->getLabel(),
            self::BOOKED->value => self::BOOKED->getLabel(),
            self::RESERVED->value => self::RESERVED->getLabel(),
        ];

        $returnStatuses[$currentStatus->value] = $currentStatus->getLabel();

        return $returnStatuses;
    }
}

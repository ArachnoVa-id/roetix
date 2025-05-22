<?php

namespace App\Enums;

use App\Enums\Traits\BaseEnumTrait;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketStatus: string implements HasLabel, HasColor
{
    use BaseEnumTrait;

    // Get by version
    public static function getByVersion(string $version, EnumVersionType $mode = EnumVersionType::DEFAULT): array|string
    {
        return match ($version) {
            'v1' => match ($mode) {
                'array' => [
                    'available',
                    'booked',
                    'reserved',
                    'in_transaction',
                ],
                'default' => 'available',
            },
            default => throw new \InvalidArgumentException("Version {$version} not supported."),
        };
    }

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

    public function getIcon(): string
    {
        return match ($this) {
            self::AVAILABLE => 'heroicon-o-check-circle',
            self::BOOKED => 'heroicon-o-clock',
            self::RESERVED => 'heroicon-o-shield-exclamation',
            self::IN_TRANSACTION => 'heroicon-o-exclamation-triangle'
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

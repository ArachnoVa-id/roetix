<?php

namespace App\Enums;

use App\Enums\Traits\BaseEnumTrait;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketOrderStatus: string implements HasLabel, HasColor
{
    use BaseEnumTrait;

    // Get by version
    public static function getByVersion(string $version, EnumVersionType $mode = EnumVersionType::DEFAULT): array|string
    {
        return match ($version) {
            'v1' => match ($mode) {
                'array' => [
                    'enabled',
                    'scanned',
                    'deactivated',
                ],
                'default' => 'enabled',
            },
            default => throw new \InvalidArgumentException("Version {$version} not supported."),
        };
    }

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
            self::SCANNED => Color::Blue,
            self::DEACTIVATED => Color::Red,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ENABLED => 'heroicon-o-check-circle',
            self::SCANNED => 'heroicon-o-check-circle',
            self::DEACTIVATED => 'heroicon-o-x-circle',
        };
    }

    public static function editableOptions(TicketOrderStatus $currentStatus)
    {
        $returnStatuses = [
            self::ENABLED->value => self::ENABLED->getLabel(),
            self::DEACTIVATED->value => self::DEACTIVATED->getLabel(),
        ];

        $returnStatuses[$currentStatus->value] = $currentStatus->getLabel();

        return $returnStatuses;
    }
}

<?php

namespace App\Enums;

use App\Enums\Traits\BaseEnumTrait;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentGateway: string implements HasLabel, HasColor
{
    use BaseEnumTrait;

    // Get by version
    public static function getByVersion(string $version, EnumVersionType $mode = EnumVersionType::DEFAULT): array|string
    {
        return match ($version) {
            'v1' => match ($mode) {
                EnumVersionType::ARRAY => [
                    'midtrans',
                    'faspay',
                    'tripay',
                ],
                EnumVersionType::DEFAULT => 'midtrans',
                default => throw new \InvalidArgumentException("Mode {$mode} not supported."),
            },
            default => throw new \InvalidArgumentException("Version {$version} not supported."),
        };
    }

    case MIDTRANS = 'midtrans';
    case FASPAY = 'faspay';
    case TRIPAY = 'tripay';

    public function getLabel(): string
    {
        return match ($this) {
            self::MIDTRANS => 'Midtrans',
            self::FASPAY => 'Faspay',
            self::TRIPAY => 'Tripay'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::MIDTRANS => Color::Blue,
            self::FASPAY => Color::Green,
            self::TRIPAY => Color::Orange
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::MIDTRANS => 'heroicon-o-credit-card',
            self::FASPAY => 'heroicon-o-credit-card',
            self::TRIPAY => 'heroicon-o-credit-card'
        };
    }

    public static function editableOptions(OrderStatus $currentStatus)
    {
        $returnStatuses = [
            self::MIDTRANS->value => self::MIDTRANS->getLabel(),
            self::FASPAY->value => self::FASPAY->getLabel(),
            self::TRIPAY->value => self::TRIPAY->getLabel(),
        ];

        $returnStatuses[$currentStatus->value] = $currentStatus->getLabel();

        return $returnStatuses;
    }
}

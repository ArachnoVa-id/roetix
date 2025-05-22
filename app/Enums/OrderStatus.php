<?php

namespace App\Enums;

use App\Enums\Traits\BaseEnumTrait;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasLabel, HasColor
{
    use BaseEnumTrait;

    // Get by version
    public static function getByVersion(string $version, string $mode = 'default'): array|string
    {
        return match ($version) {
            'v1' => match ($mode) {
                'array' => [
                    'pending',
                    'completed',
                    'cancelled',
                    'expired',
                ],
                'default' => 'pending',
            },
            default => throw new \InvalidArgumentException("Version {$version} not supported."),
        };
    }

    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => Color::Blue,
            self::COMPLETED => Color::Green,
            self::CANCELLED => Color::Red,
            self::EXPIRED => Color::Gray
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::CANCELLED => 'heroicon-o-x-circle',
            self::EXPIRED => 'heroicon-o-exclamation-triangle'
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

            case self::EXPIRED:
                $returnStatuses[self::EXPIRED->value] = self::EXPIRED->getLabel();
                break;
        }

        return $returnStatuses;
    }
}

<?php

namespace App\Enums;

use App\Enums\Traits\BaseEnumTrait;
use Filament\Support\Contracts\HasLabel;

enum OrderType: string implements HasLabel
{
    use BaseEnumTrait;

    case AUTO = 'auto';
    case MANUAL = 'manual';
    case TRANSFER = 'trans';
    case UNKNOWN = 'unknown';

    public function getLabel(): string
    {
        return match ($this) {
            self::AUTO => 'Auto',
            self::MANUAL => 'manual',
            self::TRANSFER => 'trans',
            self::UNKNOWN => 'unknown',
        };
    }
}

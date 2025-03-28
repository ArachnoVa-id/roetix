<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OrderType: string implements HasLabel
{
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

    public static function fromLabel(string $label): self
    {
        foreach (self::cases() as $case) {
            if ($case->getLabel() === $label) {
                return $case;
            }
        }

        throw new \ValueError("\"$label\" is not a valid label for enum " . self::class);
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => $case->getLabel(), self::cases());
    }

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}

<?php

namespace App\Enums\Traits;

trait BaseEnumTrait
{
    public static function fromLabel(string $label): static
    {
        foreach (self::cases() as $case) {
            if ($case->getLabel() === $label) {
                return $case;
            }
        }

        throw new \ValueError("\"$label\" is not a valid label for enum " . static::class);
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => $case->getLabel(), self::cases());
    }

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function allOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }
        return $options;
    }
}

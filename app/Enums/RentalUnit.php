<?php

namespace App\Enums;

enum RentalUnit: string
{
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';

    public function label(): string
    {
        return match ($this) {
            self::Hour => 'Saatlik',
            self::Day => 'Günlük',
            self::Week => 'Haftalık',
        };
    }

    /** Bir birimin dakika cinsinden uzunluğu. */
    public function minutes(): int
    {
        return match ($this) {
            self::Hour => 60,
            self::Day => 1440,
            self::Week => 10080,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($u) => [$u->value => $u->label()])->all();
    }
}

<?php

namespace App\Enums;

enum ExtraCalculation: string
{
    case Fixed = 'fixed';
    case PerPerson = 'per_person';
    case PerDay = 'per_day';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Sabit',
            self::PerPerson => 'Kişi Başı',
            self::PerDay => 'Gün Başı',
        };
    }
}

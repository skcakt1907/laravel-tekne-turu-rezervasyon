<?php

namespace App\Enums;

enum YachtStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Taslak',
            self::Pending => 'Onay Bekliyor',
            self::Published => 'Yayında',
            self::Rejected => 'Reddedildi',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Published => 'success',
            self::Rejected => 'danger',
        };
    }
}

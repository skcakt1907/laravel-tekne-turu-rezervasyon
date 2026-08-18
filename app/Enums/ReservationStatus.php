<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Talep Alındı',
            self::Approved => 'Onaylandı',
            self::Rejected => 'Reddedildi',
            self::Cancelled => 'İptal Edildi',
            self::Completed => 'Tamamlandı',
            self::NoShow => 'Gerçekleşmedi',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected, self::Cancelled, self::NoShow => 'danger',
            self::Completed => 'info',
        };
    }

    /** Takvimi kilitleyen durumlar — YALNIZCA onay kilitler (yol haritası kararı). */
    public function blocksCalendar(): bool
    {
        return in_array($this, [self::Approved, self::Completed], true);
    }

    /** Komisyon dökümüne giren durumlar. */
    public function countsForCommission(): bool
    {
        return $this === self::Completed;
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all();
    }
}

<?php

namespace App\Filament\Owner\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Yat sahibi paneli kaynaklarini oturum sahibine kilitler.
 *
 * Hem liste sorgusu hem rota baglama sorgusu daraltilir: aksi halde baska bir
 * sahibin kaydinin id'si adres cubuguna yazilarak duzenleme sayfasi acilabilirdi.
 */
trait ScopedToOwner
{
    public static function getEloquentQuery(): Builder
    {
        return static::scopeToOwner(parent::getEloquentQuery());
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::scopeToOwner(parent::getRecordRouteBindingEloquentQuery());
    }

    protected static function scopeToOwner(Builder $query): Builder
    {
        $ownerId = auth()->id();
        $column = static::ownerColumn();

        if (str_contains($column, '.')) {
            [$relation, $field] = explode('.', $column, 2);

            return $query->whereHas($relation, fn (Builder $query) => $query->where($field, $ownerId));
        }

        return $query->where($column, $ownerId);
    }

    /** Iliskili sutun; iliski uzerinden ise "iliski.sutun" biciminde. */
    protected static function ownerColumn(): string
    {
        return 'owner_id';
    }
}

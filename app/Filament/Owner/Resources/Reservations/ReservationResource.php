<?php

namespace App\Filament\Owner\Resources\Reservations;

use App\Filament\Owner\Resources\Reservations\Pages\ListReservations;
use App\Filament\Owner\Resources\Reservations\Schemas\ReservationForm;
use App\Filament\Owner\Resources\Reservations\Tables\ReservationsTable;
use App\Filament\Owner\Concerns\ScopedToOwner;
use App\Models\Reservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReservationResource extends Resource
{
    use ScopedToOwner;

    protected static ?string $model = Reservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'rezervasyon';

    protected static ?string $pluralModelLabel = 'rezervasyonlarım';

    protected static ?string $navigationLabel = 'Rezervasyonlarım';

    protected static ?string $recordTitleAttribute = 'code';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', \App\Enums\ReservationStatus::Pending)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return ReservationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReservationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReservations::route('/'),
        ];
    }
}

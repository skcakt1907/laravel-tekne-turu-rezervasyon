<?php

namespace App\Filament\Resources\Reservations;

use App\Filament\Resources\Reservations\Pages\EditReservation;
use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Filament\Resources\Reservations\Schemas\ReservationForm;
use App\Filament\Resources\Reservations\Tables\ReservationsTable;
use App\Filament\Resources\Reservations\RelationManagers\LogsRelationManager;
use App\Models\Reservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Rezervasyonlar';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'rezervasyon';

    protected static ?string $pluralModelLabel = 'rezervasyonlar';

    protected static ?string $navigationLabel = 'Rezervasyonlar';

    protected static ?string $recordTitleAttribute = 'code';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', \App\Enums\ReservationStatus::Pending)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', \App\Enums\ReservationStatus::Pending)
            ->whereNotNull('escalated_at')->exists() ? 'danger' : 'warning';
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
            LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReservations::route('/'),
            'edit' => EditReservation::route('/{record}/edit'),
        ];
    }
}

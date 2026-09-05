<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Bu müşterinin üyeliğine bağlı geçmiş/mevcut rezervasyonları — salt okunur. */
class ReservationsRelationManager extends RelationManager
{
    protected static string $relationship = 'reservations';

    protected static ?string $title = 'Rezervasyonlar';

    protected static ?string $modelLabel = 'rezervasyon';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Kod')->copyable(),
                TextColumn::make('yacht.slug')
                    ->label('Yat')
                    ->getStateUsing(fn (Reservation $record) => $record->yacht->getTranslation('name', 'tr')),
                TextColumn::make('starts_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (ReservationStatus $state) => $state->label())
                    ->color(fn (ReservationStatus $state) => $state->color()),
                TextColumn::make('estimated_total')
                    ->label('Tahmini')
                    ->money(fn (Reservation $record) => $record->currency),
            ])
            ->headerActions([])
            ->recordActions([]);
    }
}

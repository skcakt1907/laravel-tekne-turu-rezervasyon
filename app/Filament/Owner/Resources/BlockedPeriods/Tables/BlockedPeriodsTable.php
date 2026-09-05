<?php

namespace App\Filament\Owner\Resources\BlockedPeriods\Tables;

use App\Models\BlockedPeriod;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BlockedPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('yacht.slug')
                    ->label('Tur')
                    ->getStateUsing(fn (BlockedPeriod $record) => $record->yacht->getTranslation('name', 'tr')),
                TextColumn::make('starts_at')->label('Baslangic')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('ends_at')->label('Bitis')->dateTime('d.m.Y H:i'),
                TextColumn::make('reason')
                    ->label('Sebep')
                    ->badge()
                    ->color(fn (string $state) => $state === 'reservation' ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'reservation' => 'Rezervasyon',
                        'maintenance' => 'Bakim',
                        default => 'Ozel kullanim',
                    }),
                TextColumn::make('reservation.code')->label('Rezervasyon')->default('-'),
                TextColumn::make('note')->label('Not')->limit(40)->default('-'),
            ])
            ->defaultSort('starts_at')
            ->filters([
                SelectFilter::make('reason')
                    ->label('Sebep')
                    ->options([
                        'reservation' => 'Rezervasyon',
                        'manual' => 'Ozel kullanim',
                        'maintenance' => 'Bakim',
                    ]),
            ])
            ->recordActions([
                // Rezervasyondan gelen bloklar elle silinmez; rezervasyon iptal edilmeli.
                EditAction::make()->visible(fn (BlockedPeriod $record) => $record->reason !== 'reservation'),
                DeleteAction::make()->visible(fn (BlockedPeriod $record) => $record->reason !== 'reservation'),
            ])
            ->emptyStateHeading('Kapali tarih yok')
            ->emptyStateDescription('Bakim veya ozel kullanim icin takviminizi elle kapatabilirsiniz.');
    }
}

<?php

namespace App\Filament\Resources\Locations\Tables;

use App\Models\Location;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ad')
                    ->getStateUsing(fn (Location $record) => $record->getTranslation('name', 'tr'))
                    ->description(fn (Location $record) => $record->parent?->getTranslation('name', 'tr'))
                    ->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
                    ->sortable(),
                TextColumn::make('level')
                    ->label('Seviye')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => match ($state) {
                        Location::LEVEL_COUNTRY => 'Ulke',
                        Location::LEVEL_REGION => 'Bolge',
                        default => 'Liman',
                    })
                    ->color(fn (int $state) => match ($state) {
                        Location::LEVEL_COUNTRY => 'gray',
                        Location::LEVEL_REGION => 'info',
                        default => 'success',
                    }),
                TextColumn::make('slug')->label('Slug')->toggleable(),
                TextColumn::make('yachts_count')->label('Yat')->counts('yachts'),
                IconColumn::make('is_featured')->label('Populer')->boolean(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('level')
            ->filters([
                SelectFilter::make('level')
                    ->label('Seviye')
                    ->options([
                        Location::LEVEL_COUNTRY => 'Ulke',
                        Location::LEVEL_REGION => 'Bolge',
                        Location::LEVEL_PORT => 'Liman',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

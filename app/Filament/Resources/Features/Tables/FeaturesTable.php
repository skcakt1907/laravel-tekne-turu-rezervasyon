<?php

namespace App\Filament\Resources\Features\Tables;

use App\Models\Feature;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeaturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ozellik')
                    ->getStateUsing(fn (Feature $record) => $record->getTranslation('name', 'tr'))
                    ->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")),
                TextColumn::make('group')->label('Grup')->badge()->color('gray'),
                TextColumn::make('yachts_count')->label('Tur')->counts('yachts'),
                TextColumn::make('sort')->label('Sira')->sortable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('sort')
            ->groups(['group'])
            ->filters([
                SelectFilter::make('group')
                    ->label('Grup')
                    ->options([
                        'konfor' => 'Konfor',
                        'mutfak' => 'Mutfak',
                        'su_sporlari' => 'Su sporlari',
                        'guvenlik' => 'Guvenlik',
                        'teknik' => 'Teknik',
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

<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Ad soyad')->searchable()->sortable(),
                TextColumn::make('email')->label('E-posta')->searchable()->copyable(),
                TextColumn::make('phone')->label('Telefon')->searchable()->toggleable(),
                TextColumn::make('reservations_count')
                    ->label('Rezervasyon')
                    ->counts('reservations')
                    ->sortable()
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('İlk kayıt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

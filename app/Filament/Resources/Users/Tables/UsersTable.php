<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ad soyad')
                    ->description(fn (User $record) => $record->company_name)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')->label('E-posta')->searchable()->copyable(),
                TextColumn::make('phone')->label('Telefon')->toggleable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('created_at')->label('Kayit')->date('d.m.Y')->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')->label('Aktiflik'),
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

<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('name')->label('Ad soyad')->searchable(),
                TextColumn::make('email')->label('E-posta')->searchable()->copyable(),
                TextColumn::make('phone')->label('Telefon')->toggleable(),
                TextColumn::make('subject')->label('Konu')->default('-')->limit(40),
                TextColumn::make('message')->label('Mesaj')->limit(60)->wrap(),
                IconColumn::make('read_at')->label('Okundu')->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('read_at')
                    ->label('Okunma durumu')
                    ->nullable()
                    ->placeholder('Tumu')
                    ->trueLabel('Okunanlar')
                    ->falseLabel('Okunmayanlar'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Oku')
                    ->after(fn (ContactMessage $record) => $record->read_at
                        ? null
                        : $record->forceFill(['read_at' => now()])->save()),
                Action::make('markUnread')
                    ->label('Okunmadi isaretle')
                    ->icon('heroicon-o-envelope')
                    ->visible(fn (ContactMessage $record) => $record->read_at !== null)
                    ->action(fn (ContactMessage $record) => $record->forceFill(['read_at' => null])->save()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

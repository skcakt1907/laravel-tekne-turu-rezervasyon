<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state) => $state->label())
                    ->color(fn (UserRole $state) => match ($state) {
                        UserRole::Admin => 'danger',
                        UserRole::Owner => 'warning',
                        UserRole::Customer => 'gray',
                    }),
                TextColumn::make('yachts_count')->label('Tur')->counts('yachts'),
                IconColumn::make('is_approved')->label('Onayli')->boolean(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('created_at')->label('Kayit')->date('d.m.Y')->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options(collect(UserRole::cases())
                        ->mapWithKeys(fn (UserRole $r) => [$r->value => $r->label()])->all()),
                TernaryFilter::make('is_approved')->label('Onay durumu'),
                TernaryFilter::make('is_active')->label('Aktiflik'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Onayla')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (User $record) => $record->role === UserRole::Owner && ! $record->is_approved)
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->forceFill(['is_approved' => true])->save();
                        Notification::make()->title('Tur sahibi onaylandi.')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

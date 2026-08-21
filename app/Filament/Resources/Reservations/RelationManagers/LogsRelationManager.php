<?php

namespace App\Filament\Resources\Reservations\RelationManagers;

use App\Models\ReservationLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Denetim izi - kim, ne zaman, hangi kanaldan ne yapti. */
class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'Gecmis';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Zaman')->dateTime('d.m.Y H:i'),
                TextColumn::make('action')
                    ->label('Eylem')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'created' => 'Talep olusturuldu',
                        'approved' => 'Onaylandi',
                        'rejected' => 'Reddedildi',
                        'cancelled' => 'Iptal edildi',
                        'completed' => 'Tamamlandi',
                        'no_show' => 'Gerceklesmedi',
                        default => $state,
                    }),
                TextColumn::make('channel')
                    ->label('Kanal')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'panel' => 'Panel',
                        'whatsapp' => 'WhatsApp',
                        'link' => 'Baglanti',
                        'system' => 'Sistem',
                        default => $state,
                    }),
                TextColumn::make('user.name')->label('Kullanici')->default('-'),
                TextColumn::make('note')->label('Not')->limit(60)->default('-'),
                TextColumn::make('ip')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}

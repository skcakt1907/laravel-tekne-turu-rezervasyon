<?php

namespace App\Filament\Resources\MessageLogs\Tables;

use App\Models\MessageLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Giden tum e-posta ve WhatsApp mesajlari, teslim durumu, hata sebebi. */
class MessageLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Zaman')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('channel')
                    ->label('Kanal')
                    ->badge()
                    ->color(fn (string $state) => $state === 'whatsapp' ? 'success' : 'info')
                    ->formatStateUsing(fn (string $state) => $state === 'whatsapp' ? 'WhatsApp' : 'E-posta'),
                TextColumn::make('template')->label('Sablon')->searchable(),
                TextColumn::make('recipient')->label('Alici')->searchable()->copyable(),
                TextColumn::make('locale')->label('Dil')->badge()->color('gray'),
                TextColumn::make('related_id')
                    ->label('Rezervasyon')
                    ->getStateUsing(fn (MessageLog $record) => $record->related?->code ?? '-'),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'sent', 'delivered', 'read' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'queued' => 'Kuyrukta',
                        'sent' => 'Gonderildi',
                        'delivered' => 'Teslim edildi',
                        'read' => 'Okundu',
                        'failed' => 'Basarisiz',
                        default => $state,
                    }),
                TextColumn::make('error')->label('Hata')->limit(60)->toggleable()->color('danger'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('channel')
                    ->label('Kanal')
                    ->options(['mail' => 'E-posta', 'whatsapp' => 'WhatsApp']),
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'queued' => 'Kuyrukta',
                        'sent' => 'Gonderildi',
                        'delivered' => 'Teslim edildi',
                        'read' => 'Okundu',
                        'failed' => 'Basarisiz',
                    ]),
                SelectFilter::make('template')
                    ->label('Sablon')
                    ->options(fn () => MessageLog::query()
                        ->distinct()
                        ->orderBy('template')
                        ->pluck('template', 'template')
                        ->filter()
                        ->all()),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}

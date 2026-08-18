<?php

namespace App\Filament\Resources\Yachts\Tables;

use App\Enums\YachtStatus;
use App\Models\Location;
use App\Models\Yacht;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class YachtsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover')
                    ->label('')
                    ->getStateUsing(fn (Yacht $record) => $record->coverUrl())
                    ->defaultImageUrl(asset('images/yacht-placeholder.svg'))
                    ->height(44),
                TextColumn::make('name')
                    ->label('Yat')
                    ->getStateUsing(fn (Yacht $record) => $record->getTranslation('name', 'tr'))
                    ->description(fn (Yacht $record) => config('yacht.types')[$record->type] ?? $record->type)
                    ->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Sahibi')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('location_id')
                    ->label('Liman')
                    ->getStateUsing(fn (Yacht $record) => $record->location?->getTranslation('name', 'tr') ?? '—')
                    ->toggleable(),
                TextColumn::make('units')
                    ->label('Birimler')
                    ->getStateUsing(function (Yacht $record) {
                        $map = ['hour' => 'Saatlik', 'day' => 'Günlük', 'week' => 'Haftalık'];

                        return collect($record->activeUnits())->map(fn ($u) => $map[$u])->implode(', ') ?: '—';
                    })
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (YachtStatus $state) => $state->label())
                    ->color(fn (YachtStatus $state) => $state->color())
                    ->sortable(),
                IconColumn::make('is_open')
                    ->label('Rez. açık')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('reservations_count')
                    ->label('Rez.')
                    ->counts('reservations')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Eklendi')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(collect(YachtStatus::cases())
                        ->mapWithKeys(fn (YachtStatus $s) => [$s->value => $s->label()])->all()),
                SelectFilter::make('owner')
                    ->label('Yat sahibi')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('location_id')
                    ->label('Liman')
                    ->options(fn () => Location::where('level', Location::LEVEL_PORT)
                        ->get()
                        ->mapWithKeys(fn (Location $l) => [$l->id => $l->getTranslation('name', 'tr')])),
                SelectFilter::make('type')
                    ->label('Yat tipi')
                    ->options(config('yacht.types')),
                TernaryFilter::make('is_open')->label('Rezervasyona açık'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('publish')
                        ->label('Yayına al')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Yacht $record) => $record->status !== YachtStatus::Published)
                        ->requiresConfirmation()
                        ->action(function (Yacht $record) {
                            $min = (int) config('yacht.min_photos', 0);

                            if ($record->photos()->count() < $min) {
                                Notification::make()
                                    ->title("İlan yayına alınamadı: en az {$min} fotoğraf gerekiyor.")
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $record->forceFill([
                                'status' => YachtStatus::Published,
                                'published_at' => $record->published_at ?? now(),
                                'reject_reason' => null,
                            ])->save();

                            Notification::make()->title('İlan yayına alındı.')->success()->send();
                        }),
                    Action::make('unpublish')
                        ->label('Yayından çek')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->visible(fn (Yacht $record) => $record->status === YachtStatus::Published)
                        ->requiresConfirmation()
                        ->action(function (Yacht $record) {
                            $record->forceFill(['status' => YachtStatus::Draft])->save();
                            Notification::make()->title('İlan yayından çekildi.')->success()->send();
                        }),
                    Action::make('reject')
                        ->label('Reddet')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Yacht $record) => $record->status === YachtStatus::Pending)
                        ->schema([
                            Textarea::make('reason')
                                ->label('Red gerekçesi')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (Yacht $record, array $data) {
                            $record->forceFill([
                                'status' => YachtStatus::Rejected,
                                'reject_reason' => $data['reason'],
                            ])->save();

                            Notification::make()->title('İlan reddedildi.')->success()->send();
                        }),
                    Action::make('toggleOpen')
                        ->label(fn (Yacht $record) => $record->is_open ? 'Rezervasyonu kapat' : 'Rezervasyonu aç')
                        ->icon('heroicon-o-power')
                        ->action(function (Yacht $record) {
                            $record->update(['is_open' => ! $record->is_open]);
                            Notification::make()->title('Şalter güncellendi.')->success()->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

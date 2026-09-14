<?php

namespace App\Filament\Resources\Reservations\Tables;

use App\Enums\RentalUnit;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\ReservationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kod')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('yacht.slug')
                    ->label('Tur')
                    ->getStateUsing(fn (Reservation $record) => $record->yacht->getTranslation('name', 'tr'))
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas(
                        'yacht',
                        fn (Builder $query) => $query->where('name', 'like', "%{$search}%")
                    )),
                TextColumn::make('customer_name')
                    ->label('Müşteri')
                    ->description(fn (Reservation $record) => $record->customer_phone)
                    ->searchable(['customer_name', 'customer_email', 'customer_phone']),
                TextColumn::make('starts_at')
                    ->label('Tarih')
                    ->getStateUsing(fn (Reservation $record) => $record->starts_at->format('d.m.Y H:i'))
                    ->description(fn (Reservation $record) => $record->ends_at->format('d.m.Y H:i').' bitiş')
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('Birim')
                    ->badge()
                    ->formatStateUsing(fn (RentalUnit $state) => $state->label())
                    ->toggleable(),
                TextColumn::make('estimated_total')
                    ->label('Tutar')
                    ->money(fn (Reservation $record) => $record->currency)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (ReservationStatus $state) => $state->label())
                    ->color(fn (ReservationStatus $state) => $state->color())
                    ->description(fn (Reservation $record) => $record->hasCancelRequest()
                        ? 'Müşteri iptal talep etti'
                        : null)
                    ->sortable(),
                IconColumn::make('escalated_at')
                    ->label('Müdahale')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->getStateUsing(fn (Reservation $record) => $record->escalated_at !== null
                        && $record->status === ReservationStatus::Pending),
                TextColumn::make('created_at')
                    ->label('Geldi')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(ReservationStatus::options())
                    ->multiple(),
                Filter::make('needs_attention')
                    ->label('Müdahale gerekiyor')
                    ->query(fn (Builder $query) => $query
                        ->where('status', ReservationStatus::Pending)
                        ->whereNotNull('escalated_at'))
                    ->toggle(),
                Filter::make('cancel_requested')
                    ->label('İptal talebi olanlar')
                    ->query(fn (Builder $query) => $query->whereNotNull('cancel_requested_at')
                        ->whereIn('status', [ReservationStatus::Pending, ReservationStatus::Approved]))
                    ->toggle(),
                Filter::make('upcoming')
                    ->label('Yaklaşan gidişler')
                    ->query(fn (Builder $query) => $query
                        ->where('status', ReservationStatus::Approved)
                        ->whereBetween('starts_at', [now(), now()->addDays(14)]))
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Onayla')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Reservation $record) => $record->isPending())
                        ->requiresConfirmation()
                        ->modalDescription('Onaylandığında tarih kapanır ve komisyon oranı dondurulur.')
                        ->action(function (Reservation $record) {
                            try {
                                app(ReservationService::class)->approve($record, auth()->user(), 'panel');
                                Notification::make()->title('Rezervasyon onaylandı.')->success()->send();
                            } catch (Throwable $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();
                            }
                        }),
                    Action::make('reject')
                        ->label('Reddet')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Reservation $record) => $record->isPending())
                        ->schema([
                            Textarea::make('reason')->label('Gerekçe')->required()->maxLength(500),
                        ])
                        ->action(function (Reservation $record, array $data) {
                            try {
                                app(ReservationService::class)->reject($record, $data['reason'], auth()->user(), 'panel');
                                Notification::make()->title('Talep reddedildi.')->success()->send();
                            } catch (Throwable $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();
                            }
                        }),
                    Action::make('approveCancellation')
                        ->label('İptal talebini kabul et')
                        ->icon('heroicon-o-check')
                        ->color('danger')
                        ->visible(fn (Reservation $record) => $record->hasCancelRequest())
                        ->requiresConfirmation()
                        ->modalDescription('Rezervasyon iptal edilir ve tarih tekrar satışa açılır.')
                        ->action(function (Reservation $record) {
                        try {
                            app(ReservationService::class)->cancel(
                                $record,
                                'Müşteri talebi: '.$record->cancel_request_reason,
                                auth()->user(),
                                'panel'
                            );
                            Notification::make()->title('Rezervasyon iptal edildi.')->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                    Action::make('dismissCancellation')
                        ->label('İptal talebini reddet')
                        ->icon('heroicon-o-x-mark')
                        ->color('gray')
                        ->visible(fn (Reservation $record) => $record->hasCancelRequest())
                        ->requiresConfirmation()
                        ->modalDescription('Rezervasyon aynen devam eder.')
                        ->action(function (Reservation $record) {
                        $record->forceFill([
                            'cancel_requested_at' => null,
                            'cancel_request_reason' => null,
                        ])->save();
                        Notification::make()->title('İptal talebi reddedildi.')->success()->send();
                    }),
                    Action::make('cancelReservation')
                        ->label('İptal et')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->visible(fn (Reservation $record) => in_array($record->status, [
                            ReservationStatus::Pending, ReservationStatus::Approved,
                        ], true))
                        ->schema([
                            Textarea::make('reason')->label('Not')->maxLength(500),
                        ])
                        ->action(function (Reservation $record, array $data) {
                            try {
                                app(ReservationService::class)->cancel($record, $data['reason'] ?? null, auth()->user(), 'panel');
                                Notification::make()->title('Rezervasyon iptal edildi, tarih açıldı.')->success()->send();
                            } catch (Throwable $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();
                            }
                        }),
                    Action::make('noShow')
                        ->label('Gerçekleşmedi')
                        ->icon('heroicon-o-user-minus')
                        ->color('gray')
                        ->visible(fn (Reservation $record) => $record->status === ReservationStatus::Approved
                            && $record->ends_at->isPast())
                        ->requiresConfirmation()
                        ->action(function (Reservation $record) {
                            app(ReservationService::class)->markNoShow($record, auth()->user());
                            Notification::make()->title('Gerçekleşmedi olarak işaretlendi.')->success()->send();
                        }),
                ]),
            ]);
    }
}

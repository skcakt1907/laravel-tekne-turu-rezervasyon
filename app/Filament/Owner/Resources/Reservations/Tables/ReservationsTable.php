<?php

namespace App\Filament\Owner\Resources\Reservations\Tables;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\AvailabilityService;
use App\Services\ReservationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
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
                TextColumn::make('code')->label('Kod')->searchable()->weight('bold'),
                TextColumn::make('yacht.slug')
                    ->label('Yat')
                    ->getStateUsing(fn (Reservation $record) => $record->yacht->getTranslation('name', 'tr')),
                TextColumn::make('customer_name')
                    ->label('Müşteri')
                    ->description(fn (Reservation $record) => $record->status === ReservationStatus::Approved
                        ? $record->customer_phone
                        : 'İletişim bilgileri onaydan sonra görünür')
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->label('Tarih')
                    ->getStateUsing(fn (Reservation $record) => $record->starts_at->format('d.m.Y H:i'))
                    ->description(fn (Reservation $record) => 'bitiş '.$record->ends_at->format('d.m.Y H:i'))
                    ->sortable(),
                TextColumn::make('guests')->label('Kişi'),
                TextColumn::make('estimated_total')
                    ->label('Tahmini')
                    ->money(fn (Reservation $record) => $record->currency)
                    ->description(fn (Reservation $record) => $record->commission_amount
                        ? 'komisyon '.money($record->commission_amount, $record->currency)
                        : null)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (ReservationStatus $state) => $state->label())
                    ->color(fn (ReservationStatus $state) => $state->color()),
                TextColumn::make('created_at')->label('Geldi')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(ReservationStatus::options())
                    ->multiple(),
                Filter::make('pending')
                    ->label('Yanıt bekleyenler')
                    ->query(fn (Builder $q) => $q->where('status', ReservationStatus::Pending))
                    ->toggle(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Onayla')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Reservation $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Rezervasyonu onayla')
                    ->modalDescription(function (Reservation $record) {
                        $others = app(AvailabilityService::class)
                            ->pendingRequestCount($record->yacht, $record->starts_at, $record->ends_at, $record->id);

                        $note = 'Onayladığınızda bu tarih takviminizde kapanır.';

                        return $others > 0
                            ? $note." Aynı tarihte bekleyen {$others} talep daha var, onlar açıkta kalacak."
                            : $note;
                    })
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
                        Textarea::make('reason')
                            ->label('Gerekçe')
                            ->helperText('Müşteriye iletilir.')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Reservation $record, array $data) {
                        try {
                            app(ReservationService::class)->reject($record, $data['reason'], auth()->user(), 'panel');
                            Notification::make()->title('Talep reddedildi.')->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('cancelReservation')
                    ->label('İptal et')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Reservation $record) => $record->status === ReservationStatus::Approved)
                    ->schema([
                        Textarea::make('reason')->label('İptal sebebi')->required()->maxLength(500),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription('Tarih tekrar satışa açılır ve müşteriye bilgi gider.')
                    ->action(function (Reservation $record, array $data) {
                        try {
                            app(ReservationService::class)->cancel($record, $data['reason'], auth()->user(), 'panel');
                            Notification::make()->title('Rezervasyon iptal edildi.')->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Henüz talep yok')
            ->emptyStateDescription('İlanınız yayına girdiğinde talepler burada listelenir.');
    }
}

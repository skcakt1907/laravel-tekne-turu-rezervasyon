<?php

namespace App\Filament\Resources\Reservations\Schemas;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Rezervasyon panelden elle olusturulmaz; bu form kayit detayini gosterir.
 * Durum degisikligi yalnizca aksiyonlarla (ReservationService) yapilir ki
 * kilit, komisyon dondurma ve bildirimler atlanmasin.
 */
class ReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Rezervasyon')
                ->columns(3)
                ->schema([
                    TextInput::make('code')->label('Kod')->disabled(),
                    TextInput::make('status')
                        ->label('Durum')
                        ->formatStateUsing(fn (?ReservationStatus $state) => $state?->label())
                        ->disabled(),
                    TextInput::make('source')->label('Kaynak')->disabled(),
                    TextInput::make('starts_at')->label('Baslangic')->disabled(),
                    TextInput::make('ends_at')->label('Bitis')->disabled(),
                    TextInput::make('guests')->label('Kisi')->disabled(),
                ]),

            Section::make('Musteri')
                ->columns(2)
                ->schema([
                    TextInput::make('customer_name')->label('Ad soyad')->disabled(),
                    TextInput::make('customer_email')->label('E-posta')->disabled(),
                    TextInput::make('customer_phone')->label('Telefon')->disabled(),
                    TextInput::make('customer_whatsapp')->label('WhatsApp')->disabled(),
                    Textarea::make('message')->label('Musteri notu')->disabled()->columnSpanFull(),
                ]),

            Section::make('Tutar ve Komisyon')
                ->columns(4)
                ->schema([
                    TextInput::make('base_amount')->label('Kiralama')->disabled(),
                    TextInput::make('extras_amount')->label('Ek ucret')->disabled(),
                    TextInput::make('estimated_total')->label('Tahmini toplam')->disabled(),
                    TextInput::make('currency')->label('Para birimi')->disabled(),
                    TextInput::make('commission_rate')
                        ->label('Komisyon orani (%)')
                        ->helperText('Onay aninda donduruldu.')
                        ->disabled(),
                    TextInput::make('commission_amount')->label('Komisyon tutari')->disabled(),
                ]),

            Section::make('Yonetim')
                ->schema([
                    Textarea::make('admin_note')
                        ->label('Yonetici notu')
                        ->rows(3)
                        ->columnSpanFull(),
                    Textarea::make('reject_reason')
                        ->label('Red gerekcesi')
                        ->rows(2)
                        ->disabled()
                        ->visible(fn (?Reservation $record) => filled($record?->reject_reason))
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Ad soyad')
                ->required()
                ->maxLength(255),

            /*
             * E-posta müşteriyi tekilleştiren alan — rezervasyon geldiğinde
             * eşleştirme bunun üzerinden yapılıyor. Tekrarına izin verilirse
             * aynı kişi iki kayda bölünür.
             */
            TextInput::make('email')
                ->label('E-posta')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('phone')->label('Telefon')->tel()->maxLength(32),
            TextInput::make('whatsapp')->label('WhatsApp')->tel()->maxLength(32),

            Select::make('locale')
                ->label('Dil')
                ->options(['tr' => 'Türkçe', 'en' => 'English'])
                ->default('tr')
                ->required(),

            Textarea::make('admin_note')
                ->label('Yönetici notu')
                ->rows(3)
                ->columnSpanFull()
                ->helperText('Kısa hatırlatma. Uzun görüşme kayıtları için alttaki Notlar sekmesini kullan.'),
        ]);
    }
}

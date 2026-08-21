<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Ad soyad')->disabled(),
            TextInput::make('email')->label('E-posta')->disabled(),
            TextInput::make('phone')->label('Telefon')->disabled(),
            TextInput::make('subject')->label('Konu')->disabled(),
            Textarea::make('message')->label('Mesaj')->rows(6)->disabled()->columnSpanFull(),
        ]);
    }
}

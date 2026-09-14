<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Hesap')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Ad soyad')->required()->maxLength(120),
                    TextInput::make('email')
                        ->label('E-posta')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(190),
                    TextInput::make('password')
                        ->label('Şifre')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->helperText('Düzenlemede boş bırakılırsa şifre değişmez.')
                        ->minLength(8),
                ]),

            Section::make('İletişim')

                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextInput::make('phone')->label('Telefon')->tel()->maxLength(32),
                    TextInput::make('whatsapp_no')
                        ->label('WhatsApp numarası')
                        ->tel()
                        ->maxLength(32)
                        ->helperText('Bildirimler buraya gider.'),
                    Select::make('locale')
                        ->label('Dil')
                        ->options(collect(config('yacht.locales'))->map(fn ($l) => $l['name'])->all())
                        ->default('tr'),
                ]),

            Section::make('Yetki')

                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Toggle::make('is_approved')
                        ->label('Onaylı')
                        ->helperText('Tur sahibi panele yalnızca onaylıysa girebilir.'),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                    Textarea::make('notes')->label('Yönetici notu')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }
}

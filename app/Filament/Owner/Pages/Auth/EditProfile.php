<?php

namespace App\Filament\Owner\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Yat sahibi profili: iletisim + firma bilgileri.
 * Firma/IBAN alanlari komisyon faturasi icin gerekli (yol haritasi acik konu #5).
 */
class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Hesap')
                ->columns(2)
                ->schema([
                    $this->getNameFormComponent(),
                    $this->getEmailFormComponent(),
                    $this->getPasswordFormComponent(),
                    $this->getPasswordConfirmationFormComponent(),
                ]),

            Section::make('Bildirim')
                ->columns(2)
                ->description('Rezervasyon talepleri bu bilgilere gonderilir.')
                ->schema([
                    TextInput::make('phone')
                        ->label('Telefon')
                        ->tel()
                        ->required()
                        ->maxLength(32),
                    TextInput::make('whatsapp_no')
                        ->label('WhatsApp numarasi')
                        ->tel()
                        ->maxLength(32)
                        ->helperText('Bos birakilirsa telefon numarasi kullanilir.'),
                ]),

            Section::make('Firma Bilgileri')
                ->columns(2)
                ->description('Komisyon faturasi icin gereklidir.')
                ->schema([
                    TextInput::make('company_name')->label('Firma unvani')->maxLength(190),
                    TextInput::make('tax_office')->label('Vergi dairesi')->maxLength(120),
                    TextInput::make('tax_number')->label('Vergi / TC no')->maxLength(32),
                    TextInput::make('iban')->label('IBAN')->maxLength(40),
                    Textarea::make('address')->label('Adres')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }
}

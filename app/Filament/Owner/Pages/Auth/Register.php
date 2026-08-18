<?php

namespace App\Filament\Owner\Pages\Auth;

use App\Enums\UserRole;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

/**
 * Yat sahibi kaydı. Kullanıcı kendisi kaydolur; ilan yayına girmeden önce
 * admin onayı gerekir (is_approved=false ile başlar).
 */
class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            TextInput::make('company_name')
                ->label('Firma adı')
                ->maxLength(255),
            $this->getEmailFormComponent(),
            TextInput::make('phone')
                ->label('Telefon')
                ->tel()
                ->required()
                ->maxLength(32),
            TextInput::make('whatsapp_no')
                ->label('WhatsApp numarası')
                ->helperText('Rezervasyon bildirimleri bu numaraya gider. Boş bırakılırsa telefon kullanılır.')
                ->tel()
                ->maxLength(32),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }

    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        /** @var \App\Models\User $user */
        $user = $this->getUserModel()::create($data);

        $user->forceFill([
            'role' => UserRole::Owner,
            'is_approved' => false,
        ])->save();

        return $user;
    }
}

<?php

namespace App\Filament\Resources\Features\Schemas;

use App\Filament\Support\Translatable;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Translatable::tabs(fn (string $locale, bool $isDefault) => [
                    TextInput::make("name.{$locale}")
                        ->label('Ozellik adi')
                        ->required($isDefault)
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state) use ($isDefault) {
                            if ($isDefault && filled($state) && blank($get('slug'))) {
                                $set('slug', Str::slug($state));
                            }
                        }),
                ]),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(190),
                Select::make('group')
                    ->label('Grup')
                    ->options([
                        'konfor' => 'Konfor',
                        'mutfak' => 'Mutfak',
                        'su_sporlari' => 'Su sporlari',
                        'guvenlik' => 'Guvenlik',
                        'teknik' => 'Teknik',
                    ])
                    ->searchable(),
                TextInput::make('icon')
                    ->label('Ikon')
                    ->placeholder('heroicon-o-sparkles')
                    ->maxLength(60),
                TextInput::make('sort')->label('Sira')->numeric()->default(0),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}

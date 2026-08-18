<?php

namespace App\Filament\Resources\Locations\Schemas;

use App\Filament\Support\Translatable;
use App\Models\Location;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Konum')
                ->columns(2)
                ->schema([
                    Select::make('level')
                        ->label('Seviye')
                        ->options([
                            Location::LEVEL_COUNTRY => 'Ülke',
                            Location::LEVEL_REGION => 'Bölge',
                            Location::LEVEL_PORT => 'Liman',
                        ])
                        ->default(Location::LEVEL_PORT)
                        ->required()
                        ->live(),
                    Select::make('parent_id')
                        ->label('Üst konum')
                        ->options(fn (Get $get) => Location::where('level', max(1, (int) $get('level') - 1))
                            ->get()
                            ->mapWithKeys(fn (Location $l) => [$l->id => $l->getTranslation('name', 'tr')]))
                        ->searchable()
                        ->preload()
                        ->required(fn (Get $get) => (int) $get('level') > Location::LEVEL_COUNTRY),
                    TextInput::make('slug')
                        ->label('Adres eki (slug)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(190)
                        ->helperText('SEO sayfası: /liman/{slug} — ör. bodrum-yat-kiralama'),
                    TextInput::make('sort')->label('Sıra')->numeric()->default(0),
                ]),

            Section::make('İçerik')
                ->schema([
                    Translatable::tabs(fn (string $locale, bool $isDefault) => [
                        TextInput::make("name.{$locale}")
                            ->label('Ad')
                            ->required($isDefault)
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) use ($isDefault) {
                                if ($isDefault && filled($state) && blank($get('slug'))) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        Textarea::make("description.{$locale}")
                            ->label('Açıklama (SEO metni)')
                            ->rows(5)
                            ->columnSpanFull(),
                        TextInput::make("seo_title.{$locale}")->label('SEO başlığı')->maxLength(160),
                        TextInput::make("seo_description.{$locale}")->label('SEO açıklaması')->maxLength(255),
                    ]),
                ]),

            Section::make('Görsel ve Harita')
                ->columns(3)
                ->collapsible()
                ->schema([
                    FileUpload::make('cover')
                        ->label('Kapak görseli')
                        ->image()
                        ->disk('public')
                        ->directory('locations')
                        ->columnSpanFull(),
                    TextInput::make('lat')->label('Enlem')->numeric()->step(0.0000001),
                    TextInput::make('lng')->label('Boylam')->numeric()->step(0.0000001),
                    Toggle::make('is_featured')->label('Ana sayfada popüler liman'),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                ]),
        ]);
    }
}

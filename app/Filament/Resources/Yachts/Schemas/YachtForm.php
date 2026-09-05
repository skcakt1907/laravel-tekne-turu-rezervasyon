<?php

namespace App\Filament\Resources\Yachts\Schemas;

use App\Enums\UserRole;
use App\Enums\YachtStatus;
use App\Filament\Support\Translatable;
use App\Models\User;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class YachtForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('İlan İçeriği')
                    ->description('Türkçe zorunlu. İngilizce boş bırakılırsa sitede Türkçe metin gösterilir.')
                    ->schema([
                        Translatable::tabs(fn (string $locale, bool $isDefault) => [
                            TextInput::make("name.{$locale}")
                                ->label('Tur adı')
                                ->required($isDefault)
                                ->maxLength(160)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) use ($isDefault) {
                                    if ($isDefault && filled($state) && blank($get('slug'))) {
                                        $set('slug', Str::slug($state));
                                    }
                                }),
                            RichEditor::make("description.{$locale}")
                                ->label('Açıklama')
                                ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link', 'undo', 'redo'])
                                ->columnSpanFull(),
                            RichEditor::make("rules.{$locale}")
                                ->label('Kurallar / notlar')
                                ->toolbarButtons(['bold', 'italic', 'bulletList', 'link'])
                                ->columnSpanFull(),
                        ]),
                        TextInput::make('slug')
                            ->label('Adres eki (slug)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(190)
                            ->helperText('Sitede /tur/{slug} adresinde görünür.'),
                    ]),

                Section::make('Sahip ve Konum')
                    ->columns(2)
                    ->schema([
                        Select::make('owner_id')
                            ->label('Tur sahibi')
                            ->options(fn () => User::where('role', UserRole::Owner)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('type')
                            ->label('Tekne tipi')
                            ->options(config('yacht.types'))
                            ->required(),
                        Toggle::make('with_crew')
                            ->label('Kaptanlı / mürettebatlı')
                            ->default(true),
                    ]),

                Section::make('Teknik Bilgiler')
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        TextInput::make('brand')->label('Marka')->maxLength(80),
                        TextInput::make('model')->label('Model')->maxLength(80),
                        TextInput::make('build_year')->label('Yapım yılı')->numeric()->minValue(1900)->maxValue((int) date('Y') + 1),
                        TextInput::make('length_m')->label('Boy (m)')->numeric()->step(0.1),
                        TextInput::make('cabins')->label('Kabin')->numeric()->minValue(0),
                        TextInput::make('beds')->label('Yatak')->numeric()->minValue(0),
                        TextInput::make('wc')->label('WC')->numeric()->minValue(0),
                        TextInput::make('engine')->label('Motor')->maxLength(120),
                        TextInput::make('capacity')
                            ->label('Günlük tur kapasitesi')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Saatlik/günlük gezide taşınabilecek kişi'),
                        TextInput::make('sleep_capacity')
                            ->label('Konaklamalı kapasite')
                            ->numeric()
                            ->minValue(0),
                    ]),

                Section::make('Kiralama Ayarları')
                    ->description('Hangi birimlerin açık olduğunu seçin; fiyatları "Fiyatlar" sekmesinden girin.')
                    ->columns(3)
                    ->schema([
                        Toggle::make('unit_hourly')->label('Saatlik kiralama')->live(),
                        Toggle::make('unit_daily')->label('Günlük kiralama')->default(true)->live(),
                        Toggle::make('unit_weekly')->label('Haftalık kiralama')->live(),

                        Select::make('currency')
                            ->label('Para birimi')
                            ->options(fn () => collect(config('yacht.currencies'))
                                ->map(fn ($symbol, $code) => "{$code} ({$symbol})")->all())
                            ->default('EUR')
                            ->required()
                            ->helperText('Tur bazında sabittir.'),
                        TextInput::make('turnaround_minutes')
                            ->label('İki kiralama arası boşluk (dk)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Temizlik, yakıt, teslim payı. Çakışma kontrolüne dahil edilir.'),

                        TimePicker::make('day_start')
                            ->label('Gün içi başlangıç')
                            ->seconds(false)
                            ->visible(fn (Get $get) => (bool) $get('unit_hourly')),
                        TimePicker::make('day_end')
                            ->label('Gün içi bitiş')
                            ->seconds(false)
                            ->visible(fn (Get $get) => (bool) $get('unit_hourly')),

                        TimePicker::make('checkin_time')
                            ->label('Giriş saati')
                            ->seconds(false)
                            ->visible(fn (Get $get) => (bool) $get('unit_daily') || (bool) $get('unit_weekly')),
                        TimePicker::make('checkout_time')
                            ->label('Çıkış saati')
                            ->seconds(false)
                            ->visible(fn (Get $get) => (bool) $get('unit_daily') || (bool) $get('unit_weekly')),
                        Select::make('weekly_start_dow')
                            ->label('Haftalık kiralama giriş günü')
                            ->options([
                                0 => 'Pazar', 1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba',
                                4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi',
                            ])
                            ->helperText('Boş bırakılırsa her gün başlayabilir.')
                            ->visible(fn (Get $get) => (bool) $get('unit_weekly')),
                    ]),

                Section::make('Özellikler')
                    ->schema([
                        Select::make('features')
                            ->label('Tekne özellikleri')
                            ->relationship('features', 'slug')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'tr'))
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('Yayın Durumu')
                    ->columns(3)
                    ->schema([
                        Select::make('status')
                            ->label('Durum')
                            ->options(collect(YachtStatus::cases())
                                ->mapWithKeys(fn (YachtStatus $s) => [$s->value => $s->label()])->all())
                            ->default(YachtStatus::Draft->value)
                            ->required()
                            ->live()
                            ->helperText('İlan yalnızca "Yayında" iken sitede görünür.'),
                        Toggle::make('is_open')
                            ->label('Rezervasyona açık')
                            ->default(true)
                            ->helperText('Kapalıyken ilan görünür ama talep alınmaz.'),
                        Toggle::make('is_featured')
                            ->label('Ana sayfada öne çıkar'),
                        TextInput::make('reject_reason')
                            ->label('Red gerekçesi')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('status') === YachtStatus::Rejected->value)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

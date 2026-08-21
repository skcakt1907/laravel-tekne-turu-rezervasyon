<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Site ayarlari. Degerler `settings` tablosunda key/value; Setting::get cache'li,
 * kayitta cache temizlenir.
 */
class SiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Ayarlar';

    protected static ?string $navigationLabel = 'Site Ayarlari';

    protected static ?string $title = 'Site Ayarlari';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    /** @var array<int, string> */
    public const KEYS = [
        'site_name', 'site_email', 'site_phone', 'whatsapp_display_number', 'address',
        'site_description', 'google_analytics_id', 'estimate_notice_tr', 'estimate_notice_en',
    ];

    public function mount(): void
    {
        $values = [];

        foreach (self::KEYS as $key) {
            $values[$key] = Setting::get($key, '');
        }

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kimlik')
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')->label('Site adi')->required()->maxLength(120),
                        TextInput::make('site_email')->label('E-posta')->email()->maxLength(190),
                        TextInput::make('site_phone')->label('Telefon')->maxLength(40),
                        TextInput::make('whatsapp_display_number')
                            ->label('Sitede gorunen WhatsApp')
                            ->maxLength(40)
                            ->helperText('Gonderim numarasi .env icinde; bu yalnizca gorunum.'),
                        Textarea::make('address')->label('Adres')->rows(2)->columnSpanFull(),
                        Textarea::make('site_description')
                            ->label('SEO aciklamasi')
                            ->rows(2)
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('Metinler')
                    ->columns(2)
                    ->schema([
                        TextInput::make('estimate_notice_tr')->label('Tahmini tutar ibaresi (TR)')->maxLength(190),
                        TextInput::make('estimate_notice_en')->label('Tahmini tutar ibaresi (EN)')->maxLength(190),
                    ]),

                Section::make('Analitik')
                    ->schema([
                        TextInput::make('google_analytics_id')
                            ->label('Google Analytics olcum kimligi')
                            ->placeholder('G-XXXXXXXXXX')
                            ->maxLength(40)
                            ->helperText('Bos birakilirsa hicbir izleme betigi yuklenmez. Betik yalnizca ziyaretci cerezleri kabul ederse calisir.'),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Kaydet')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (self::KEYS as $key) {
            Setting::put($key, $data[$key] ?? null);
        }

        Notification::make()->title('Ayarlar kaydedildi.')->success()->send();
    }
}

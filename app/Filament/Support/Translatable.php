<?php

namespace App\Filament\Support;

use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Çok dilli form alanları. Resmî filament/spatie-laravel-translatable-plugin
 * henüz v5'i desteklemediği için (v3'te duruyor) sekmeleri kendimiz kuruyoruz.
 *
 * Alan adları "name.tr" / "name.en" olur; spatie'nin HasTranslations'ı dizi
 * atamasını setTranslations olarak karşılar. Doldurma tarafını
 * App\Filament\Concerns\FillsTranslations halleder.
 */
class Translatable
{
    /** @param  callable(string $locale, bool $isDefault): array  $fields */
    public static function tabs(callable $fields, ?string $label = null): Tabs
    {
        $locales = config('yacht.locales', ['tr' => ['name' => 'Türkçe']]);
        $default = array_key_first($locales);

        $tabs = [];
        foreach ($locales as $locale => $config) {
            $tabs[] = Tab::make($config['name'] ?? strtoupper($locale))
                ->schema($fields($locale, $locale === $default));
        }

        return Tabs::make($label ?? 'Diller')->tabs($tabs)->columnSpanFull();
    }

    /** Kayıt listelerinde varsayılan dildeki değeri göstermek için. */
    public static function default(): string
    {
        return array_key_first(config('yacht.locales', ['tr' => []]));
    }
}

<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('money')) {
    /** Tutari yat bazindaki para birimiyle bicimler. */
    function money(float|int|string|null $amount, string $currency = 'EUR'): string
    {
        $symbol = config('yacht.currencies')[$currency] ?? $currency;
        $formatted = number_format((float) $amount, 0, ',', '.');

        return $currency === 'TRY' ? "{$formatted} {$symbol}" : "{$symbol}{$formatted}";
    }
}

if (! function_exists('locale_url')) {
    /** Mevcut adresin baska bir dildeki karsiligi. TR oneksiz, digerleri /{locale}/ onekli. */
    function locale_url(string $locale): string
    {
        $locales = array_keys(config('yacht.locales', ['tr' => []]));
        $default = $locales[0] ?? 'tr';

        $segments = request()->segments();

        if (isset($segments[0]) && in_array($segments[0], $locales, true)) {
            array_shift($segments);
        }

        if ($locale !== $default) {
            array_unshift($segments, $locale);
        }

        $path = implode('/', $segments);
        $query = request()->getQueryString();

        return url($path).($query ? '?'.$query : '');
    }
}

if (! function_exists('lroute')) {
    /**
     * Aktif dilin rotasini uretir. Varsayilan dil oneksiz ("yachts.index"),
     * digerleri onekli isimle kayitli ("en.yachts.index").
     */
    function lroute(string $name, mixed $params = []): string
    {
        $locale = app()->getLocale();
        $default = array_key_first(config('yacht.locales', ['tr' => []]));

        $resolved = $locale === $default ? $name : "{$locale}.{$name}";

        return \Illuminate\Support\Facades\Route::has($resolved)
            ? route($resolved, $params)
            : route($name, $params);
    }
}

if (! function_exists('yacht_type_label')) {
    /** Yat tipi etiketi - aktif dilde. Anahtarlar config/yacht.php'de sabit. */
    function yacht_type_label(?string $key): string
    {
        if (! $key) {
            return '-';
        }

        $translated = __('site.types.'.$key);

        return $translated === 'site.types.'.$key
            ? (config('yacht.types')[$key] ?? $key)
            : $translated;
    }
}

if (! function_exists('yacht_type_options')) {
    /** @return array<string, string> */
    function yacht_type_options(): array
    {
        return collect(config('yacht.types'))
            ->mapWithKeys(fn ($label, $key) => [$key => yacht_type_label($key)])
            ->all();
    }
}

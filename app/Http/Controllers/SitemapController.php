<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Page;
use App\Models\Yacht;
use Illuminate\Support\Carbon;

/**
 * Site haritası. Her adres için diğer dillerin karşılığı `xhtml:link` ile
 * verilir — hreflang'i yalnızca sayfa içinde bırakmak arama motorları için
 * yeterli olsa da sitemap'te tekrarlamak eşleşmeyi hızlandırır.
 */
class SitemapController extends Controller
{
    public function __invoke()
    {
        $locales = array_keys(config('yacht.locales', ['tr' => []]));
        $urls = [];

        // Sabit sayfalar
        foreach ([
            ['home', [], '1.0', 'daily'],
            ['tours.index', [], '0.9', 'daily'],
            ['owner.landing', [], '0.6', 'monthly'],
            ['contact', [], '0.5', 'monthly'],
            ['reservation.lookup', [], '0.4', 'yearly'],
        ] as [$name, $params, $priority, $freq]) {
            $urls[] = $this->entry($name, $params, $locales, $priority, $freq);
        }

        // Yayındaki yatlar
        foreach (Yacht::published()->get(['slug', 'updated_at']) as $yacht) {
            $urls[] = $this->entry('tours.show', ['slug' => $yacht->slug], $locales, '0.8', 'weekly', $yacht->updated_at);
        }

        // Liman / bölge sayfaları (SEO'nun asıl çalıştığı yer)
        foreach (Location::where('is_active', true)->get(['slug', 'updated_at', 'level']) as $location) {
            $urls[] = $this->entry(
                'locations.show',
                ['slug' => $location->slug],
                $locales,
                $location->level === Location::LEVEL_PORT ? '0.7' : '0.5',
                'weekly',
                $location->updated_at
            );
        }

        // Kurumsal sayfalar
        foreach (Page::where('is_active', true)->get(['slug', 'updated_at']) as $page) {
            $urls[] = $this->entry('pages.show', ['slug' => $page->slug], $locales, '0.3', 'monthly', $page->updated_at);
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    private function entry(
        string $name,
        array $params,
        array $locales,
        string $priority,
        string $frequency,
        ?Carbon $lastmod = null,
    ): array {
        $default = $locales[0];
        $alternates = [];

        foreach ($locales as $locale) {
            $routeName = $locale === $default ? $name : "{$locale}.{$name}";
            $alternates[$locale] = \Illuminate\Support\Facades\Route::has($routeName)
                ? route($routeName, $params)
                : route($name, $params);
        }

        return [
            'loc' => $alternates[$default],
            'alternates' => $alternates,
            'lastmod' => ($lastmod ?? now())->toAtomString(),
            'priority' => $priority,
            'changefreq' => $frequency,
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Location;
use App\Models\Yacht;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /** Ana sayfa sorgulari 10 dakika onbellekte; yat kaydedilince temizlenir (YachtObserver). */
    public function __invoke()
    {
        $data = Cache::remember('home.'.app()->getLocale(), now()->addMinutes(10), fn () => [
            'featured' => Yacht::bookable()
                ->with(['photos', 'location'])
                ->orderByDesc('is_featured')
                ->orderByDesc('published_at')
                ->limit(6)
                ->get(),
            'ports' => Location::where('level', Location::LEVEL_PORT)
                ->where('is_active', true)
                ->where('is_featured', true)
                ->withCount(['yachts' => fn ($q) => $q->bookable()])
                ->orderBy('sort')
                ->limit(8)
                ->get(),
            'types' => collect(config('yacht.types'))
                ->map(fn ($label, $key) => [
                    'key' => $key,
                    'label' => yacht_type_label($key),
                    'count' => Yacht::bookable()->where('type', $key)->count(),
                ])
                ->filter(fn ($t) => $t['count'] > 0)
                ->values(),
            'faqs' => Faq::where('is_active', true)
                ->where('audience', 'customer')
                ->orderBy('sort')
                ->get(),
        ]);

        return view('home', $data);
    }
}

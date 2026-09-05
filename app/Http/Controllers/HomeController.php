<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Yacht;

class HomeController extends Controller
{
    /**
     * DIKKAT: Buradaki sorgular onbellege ALINMAZ.
     *
     * Laravel 13 varsayilan olarak onbellekten hicbir PHP sinifini geri acmaz
     * (config/cache.php -> serializable_classes = false, gadget-chain sertlestirmesi).
     * Eloquent koleksiyonunu Cache::remember ile saklarsan geri okumada
     * __PHP_Incomplete_Class doner ve sayfa 500 verir. Hiz gerekirse ID listesi
     * veya hazir dizi onbellege al, model degil.
     */
    public function __invoke()
    {
        return view('home', [
            'stats' => [
                'yachts' => Yacht::bookable()->count(),
            ],
            'featured' => Yacht::bookable()
                ->with('photos')
                ->orderByDesc('is_featured')
                ->orderByDesc('published_at')
                ->limit(6)
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
    }
}

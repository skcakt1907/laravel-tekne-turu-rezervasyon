<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Location;
use App\Models\Yacht;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', [
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
    }
}

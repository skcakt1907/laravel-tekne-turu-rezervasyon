<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\Yacht;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Services\YachtSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class YachtController extends Controller
{
    public function __construct(
        private YachtSearch $search,
        private AvailabilityService $availability,
        private PricingService $pricing,
    ) {}

    public function index(Request $request)
    {
        $yachts = $this->search->apply($request)
            ->paginate(12)
            ->withQueryString();

        return view('tours.index', [
            'yachts' => $yachts,
            'features' => $this->features(),
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $yacht = Yacht::published()
            ->with(['photos', 'features', 'rates', 'extras', 'owner'])
            ->where('slug', $slug)
            ->firstOrFail();

        $from = now()->startOfDay();
        $to = $from->copy()->addMonths(3);

        $similar = Yacht::bookable()
            ->with('photos')
            ->whereKeyNot($yacht->id)
            ->where('type', $yacht->type)
            ->limit(3)
            ->get();

        return view('tours.show', [
            'yacht' => $yacht,
            'seats' => $this->availability->seatsForRange($yacht, $from, $to),
            'rates' => $yacht->rates,
            'similar' => $similar,
            'prefill' => [
                'date' => $request->string('date')->toString(),
                'adults' => $request->integer('adults') ?: 2,
                'children' => $request->integer('children') ?: 0,
            ],
        ]);
    }

    private function features()
    {
        return Feature::where('is_active', true)
            ->orderBy('sort')
            ->get()
            ->groupBy('group');
    }
}

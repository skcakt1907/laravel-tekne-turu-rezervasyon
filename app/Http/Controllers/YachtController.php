<?php

namespace App\Http\Controllers;

use App\Enums\RentalUnit;
use App\Models\Feature;
use App\Models\Location;
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

        return view('yachts.index', [
            'yachts' => $yachts,
            'ports' => $this->ports(),
            'features' => $this->features(),
            'location' => null,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $yacht = Yacht::published()
            ->with(['photos', 'features', 'rates', 'extras', 'location', 'owner'])
            ->where('slug', $slug)
            ->firstOrFail();

        $from = now()->startOfDay();
        $to = $from->copy()->addMonths(6);

        $similar = Yacht::bookable()
            ->with('photos')
            ->whereKeyNot($yacht->id)
            ->where(fn ($q) => $q->where('location_id', $yacht->location_id)->orWhere('type', $yacht->type))
            ->limit(3)
            ->get();

        return view('yachts.show', [
            'yacht' => $yacht,
            'blocked' => $this->availability->blockedRanges($yacht, $from, $to)
                ->map(fn ($b) => [
                    'start' => $b->starts_at->toDateString(),
                    'end' => $b->ends_at->toDateString(),
                ])->values(),
            'rates' => $yacht->rates->groupBy(fn ($rate) => $rate->unit->value),
            'similar' => $similar,
            'prefill' => [
                'unit' => $this->defaultUnit($yacht, $request),
                'start' => $request->string('start')->toString(),
                'end' => $request->string('end')->toString(),
                'guests' => $request->integer('guests') ?: 2,
            ],
        ]);
    }

    /** Liman / bölge SEO sayfası — "Bodrum yat kiralama" tipi. */
    public function location(Request $request, string $slug)
    {
        $location = Location::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $query = Yacht::query()->whereIn('location_id', $location->descendantIds());

        $yachts = $this->search->apply($request, $query)
            ->paginate(12)
            ->withQueryString();

        return view('yachts.index', [
            'yachts' => $yachts,
            'ports' => $this->ports(),
            'features' => $this->features(),
            'location' => $location,
        ]);
    }

    private function defaultUnit(Yacht $yacht, Request $request): string
    {
        $requested = $request->string('unit')->toString();
        $units = $yacht->activeUnits();

        if ($requested && in_array($requested, $units, true)) {
            return $requested;
        }

        return $units[0] ?? RentalUnit::Day->value;
    }

    private function ports()
    {
        return Location::where('level', Location::LEVEL_PORT)
            ->where('is_active', true)
            ->withCount(['yachts' => fn ($q) => $q->bookable()])
            ->orderBy('sort')
            ->get();
    }

    private function features()
    {
        return Feature::where('is_active', true)
            ->orderBy('sort')
            ->get()
            ->groupBy('group');
    }
}

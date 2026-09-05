<?php

namespace App\Http\Controllers;

use App\Enums\RentalUnit;
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
        $to = $from->copy()->addMonths(6);

        $similar = Yacht::bookable()
            ->with('photos')
            ->whereKeyNot($yacht->id)
            ->where('type', $yacht->type)
            ->limit(3)
            ->get();

        return view('tours.show', [
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

    private function defaultUnit(Yacht $yacht, Request $request): string
    {
        $requested = $request->string('unit')->toString();
        $units = $yacht->activeUnits();

        if ($requested && in_array($requested, $units, true)) {
            return $requested;
        }

        return $units[0] ?? RentalUnit::Day->value;
    }

    private function features()
    {
        return Feature::where('is_active', true)
            ->orderBy('sort')
            ->get()
            ->groupBy('group');
    }
}

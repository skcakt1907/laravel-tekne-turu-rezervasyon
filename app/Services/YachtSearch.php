<?php

namespace App\Services;

use App\Models\Yacht;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Yat listesi filtreleri. Yat listesi ve liman sayfası aynı motoru kullanır.
 *
 * Tarih filtresi müsaitliği "kilitli aralık yok" olarak yorumlar: yalnızca
 * onaylı rezervasyonlar ve manuel bloklar yatı listeden düşürür (talep düşürmez).
 */
class YachtSearch
{
    public const SORTS = ['recommended', 'price_asc', 'price_desc', 'capacity_desc', 'length_desc', 'newest'];

    public function apply(Request $request, ?Builder $query = null): Builder
    {
        $query ??= Yacht::query();

        $query->bookable()->with(['photos', 'location', 'owner']);

        if ($portId = $request->integer('port')) {
            $query->where('location_id', $portId);
        }

        if ($guests = $request->integer('guests')) {
            $query->where(function (Builder $q) use ($guests) {
                $q->where('capacity', '>=', $guests)->orWhere('sleep_capacity', '>=', $guests);
            });
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($cabins = $request->integer('cabins')) {
            $query->where('cabins', '>=', $cabins);
        }

        if ($request->filled('crew')) {
            $query->where('with_crew', $request->string('crew')->toString() === 'with');
        }

        if ($min = $request->integer('price_min')) {
            $query->where('price_from', '>=', $min);
        }

        if ($max = $request->integer('price_max')) {
            $query->where('price_from', '<=', $max);
        }

        foreach ((array) $request->input('features', []) as $featureId) {
            if (! is_numeric($featureId)) {
                continue;
            }

            $query->whereHas('features', fn (Builder $q) => $q->where('features.id', (int) $featureId));
        }

        [$start, $end] = $this->dates($request);

        if ($start && $end) {
            $query->whereDoesntHave('blockedPeriods', fn (Builder $q) => $q
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start));
        }

        return $this->sort($query, $request->string('sort')->toString());
    }

    /** @return array{0: ?Carbon, 1: ?Carbon} */
    public function dates(Request $request): array
    {
        $start = $request->filled('start') ? $this->parse($request->string('start')->toString()) : null;
        $end = $request->filled('end') ? $this->parse($request->string('end')->toString()) : null;

        if ($start && $end && $end->lessThanOrEqualTo($start)) {
            return [null, null];
        }

        return [$start, $end];
    }

    private function parse(string $value): ?Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function sort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderByRaw('price_from is null, price_from asc'),
            'price_desc' => $query->orderByDesc('price_from'),
            'capacity_desc' => $query->orderByDesc('capacity'),
            'length_desc' => $query->orderByDesc('length_m'),
            'newest' => $query->orderByDesc('published_at'),
            default => $query->orderByDesc('is_featured')->orderByDesc('published_at'),
        };
    }
}

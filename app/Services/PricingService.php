<?php

namespace App\Services;

use App\Enums\RentalUnit;
use App\Models\Yacht;
use App\Models\YachtRate;
use Carbon\CarbonInterface;

/**
 * Tahmini tutar hesabı. Sitede ödeme alınmadığı için sonuç daima "tahmini"dir.
 * Sezon kuralı: dar aralık geniş aralığı ezer; hiçbiri denk gelmezse temel fiyat.
 */
class PricingService
{
    /**
     * @return array{base: float, extras: float, total: float, currency: string, breakdown: array}
     */
    public function quote(
        Yacht $yacht,
        RentalUnit $unit,
        CarbonInterface $start,
        CarbonInterface $end,
        int $guests = 1,
        array $extraIds = [],
    ): array {
        $quantity = $this->quantity($unit, $start, $end);
        $rate = $this->resolveRate($yacht, $unit, $start);

        $unitPrice = $rate ? (float) $rate->price : 0.0;
        $base = round($unitPrice * $quantity, 2);

        $days = max($start->diffInMinutes($end) / 1440, 0);
        $extras = 0.0;
        $extraLines = [];

        foreach ($yacht->extras as $extra) {
            $selected = in_array($extra->id, $extraIds, false);

            if (! $extra->is_required && ! $selected) {
                continue;
            }

            $amount = $extra->calculate($guests, $days);
            $extras += $amount;
            $extraLines[] = [
                'id' => $extra->id,
                'name' => $extra->getTranslation('name', app()->getLocale()),
                'required' => $extra->is_required,
                'amount' => round($amount, 2),
            ];
        }

        return [
            'base' => $base,
            'extras' => round($extras, 2),
            'total' => round($base + $extras, 2),
            'currency' => $yacht->currency,
            'breakdown' => [
                'unit' => $unit->value,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'rate_id' => $rate?->id,
                'rate_label' => $rate?->label,
                'extras' => $extraLines,
            ],
        ];
    }

    /** Seçilen tarihte geçerli fiyat kaydı. */
    public function resolveRate(Yacht $yacht, RentalUnit $unit, CarbonInterface $date): ?YachtRate
    {
        $rates = $yacht->relationLoaded('rates') ? $yacht->rates : $yacht->rates()->get();

        $matching = $rates
            ->where('unit', $unit)
            ->filter(function (YachtRate $rate) use ($date) {
                if ($rate->isBase()) {
                    return true;
                }

                return $date->betweenIncluded($rate->season_start, $rate->season_end);
            })
            ->sortBy(fn (YachtRate $rate) => $rate->spanDays()); // dar aralık önce

        return $matching->first();
    }

    /** Liste kartındaki "…'den başlayan fiyatlarla" değeri. */
    public function priceFrom(Yacht $yacht): ?array
    {
        $units = $yacht->activeUnits();

        if (! $units) {
            return null;
        }

        $cheapest = $yacht->rates()
            ->whereIn('unit', $units)
            ->orderBy('price')
            ->first();

        if (! $cheapest) {
            return null;
        }

        return [
            'price' => (float) $cheapest->price,
            'unit' => $cheapest->unit->value,
            'currency' => $yacht->currency,
        ];
    }

    /** Kiralama süresinin birim cinsinden miktarı (yukarı yuvarlanır). */
    public function quantity(RentalUnit $unit, CarbonInterface $start, CarbonInterface $end): float
    {
        $minutes = max($start->diffInMinutes($end), 0);

        return max(1, ceil($minutes / $unit->minutes()));
    }

    /** Yat sahibinin girdiği en az süre kuralı sağlanıyor mu? */
    public function meetsMinimum(Yacht $yacht, RentalUnit $unit, CarbonInterface $start, CarbonInterface $end): bool
    {
        $rate = $this->resolveRate($yacht, $unit, $start);

        if (! $rate) {
            return false;
        }

        return $this->quantity($unit, $start, $end) >= $rate->min_duration;
    }
}

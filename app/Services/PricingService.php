<?php

namespace App\Services;

use App\Models\Yacht;
use App\Models\YachtRate;
use Carbon\CarbonInterface;

/**
 * Tahmini tutar hesabı. Sitede ödeme alınmadığı için sonuç daima "tahmini"dir.
 * Kişi başı grup turu: yetişkin + çocuk (boşsa yetişkinle aynı) fiyatı.
 * Sezon kuralı: dar aralık geniş aralığı ezer; hiçbiri denk gelmezse temel fiyat.
 */
class PricingService
{
    /**
     * @return array{base: float, extras: float, total: float, currency: string, breakdown: array}
     */
    public function quote(
        Yacht $yacht,
        CarbonInterface $date,
        int $adults = 1,
        int $children = 0,
        array $extraIds = [],
    ): array {
        $rate = $this->resolveRate($yacht, $date);

        $adultPrice = $rate ? (float) $rate->price : 0.0;
        $childPrice = $rate ? (float) ($rate->price_child ?? $rate->price) : 0.0;
        $base = round($adultPrice * $adults + $childPrice * $children, 2);

        $guests = $adults + $children;
        $extras = 0.0;
        $extraLines = [];

        foreach ($yacht->extras as $extra) {
            $selected = in_array($extra->id, $extraIds, false);

            if (! $extra->is_required && ! $selected) {
                continue;
            }

            $amount = $extra->calculate($guests, 1);
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
                'adult_price' => $adultPrice,
                'child_price' => $childPrice,
                'adults' => $adults,
                'children' => $children,
                'rate_id' => $rate?->id,
                'rate_label' => $rate?->label,
                'extras' => $extraLines,
            ],
        ];
    }

    /** Seçilen tarihte geçerli fiyat kaydı (tek birim: günlük). */
    public function resolveRate(Yacht $yacht, CarbonInterface $date): ?YachtRate
    {
        $rates = $yacht->relationLoaded('rates') ? $yacht->rates : $yacht->rates()->get();

        return $rates
            ->filter(function (YachtRate $rate) use ($date) {
                if ($rate->isBase()) {
                    return true;
                }

                return $date->betweenIncluded($rate->season_start, $rate->season_end);
            })
            ->sortBy(fn (YachtRate $rate) => $rate->spanDays()) // dar aralık önce
            ->first();
    }

    /** Liste kartındaki "…'den başlayan fiyatlarla" değeri (yetişkin fiyatı). */
    public function priceFrom(Yacht $yacht): ?array
    {
        $cheapest = $yacht->rates()->orderBy('price')->first();

        if (! $cheapest) {
            return null;
        }

        return [
            'price' => (float) $cheapest->price,
            'unit' => $cheapest->unit->value,
            'currency' => $yacht->currency,
        ];
    }
}

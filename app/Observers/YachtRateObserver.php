<?php

namespace App\Observers;

use App\Models\YachtRate;
use App\Services\PricingService;

/**
 * price_from denormalize alanini guncel tutar. Liste sayfasindaki fiyat
 * siralama/filtresi bu alan uzerinden calisir (her satirda alt sorgu yerine).
 */
class YachtRateObserver
{
    public function __construct(private PricingService $pricing) {}

    public function saved(YachtRate $rate): void
    {
        $this->sync($rate);
    }

    public function deleted(YachtRate $rate): void
    {
        $this->sync($rate);
    }

    private function sync(YachtRate $rate): void
    {
        $yacht = $rate->yacht;

        if (! $yacht) {
            return;
        }

        $from = $this->pricing->priceFrom($yacht->fresh());

        $yacht->forceFill([
            'price_from' => $from['price'] ?? null,
            'price_from_unit' => $from['unit'] ?? null,
        ])->saveQuietly();
    }
}

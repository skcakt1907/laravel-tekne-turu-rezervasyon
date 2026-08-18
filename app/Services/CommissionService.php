<?php

namespace App\Services;

use App\Models\CommissionSetting;
use App\Models\Reservation;
use App\Models\Yacht;

/**
 * Üç kademeli oran: genel → yat sahibi → yat. En dar tanım geçerlidir.
 * Oran, rezervasyon ONAYLANDIĞI anda kayda dondurulur; sonradan değişse bile
 * geçmiş rezervasyonlar etkilenmez.
 */
class CommissionService
{
    public function rateFor(Yacht $yacht): float
    {
        $yachtRate = $this->lookup(CommissionSetting::SCOPE_YACHT, $yacht->id);

        if ($yachtRate !== null) {
            return $yachtRate;
        }

        $ownerRate = $this->lookup(CommissionSetting::SCOPE_OWNER, $yacht->owner_id);

        if ($ownerRate !== null) {
            return $ownerRate;
        }

        return $this->lookup(CommissionSetting::SCOPE_GLOBAL, null) ?? 0.0;
    }

    public function freeze(Reservation $reservation): void
    {
        $rate = $this->rateFor($reservation->yacht);

        $reservation->forceFill([
            'commission_rate' => $rate,
            'commission_amount' => round((float) $reservation->estimated_total * $rate / 100, 2),
        ]);
    }

    private function lookup(string $scope, ?int $targetId): ?float
    {
        $setting = CommissionSetting::query()
            ->where('scope', $scope)
            ->when($targetId !== null, fn ($q) => $q->where('target_id', $targetId))
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', now()))
            ->orderByDesc('effective_from')
            ->first();

        return $setting ? (float) $setting->rate : null;
    }
}

<?php

namespace App\Services;

use App\Models\BlockedPeriod;
use App\Models\Yacht;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Tek zaman modeli: saatlik/günlük/haftalık hepsi starts_at–ends_at.
 * Çakışma sorgusu: starts_at < :bitis AND ends_at > :baslangic
 * Hazırlık payı (turnaround) aralığın iki ucuna eklenerek kontrol edilir.
 */
class AvailabilityService
{
    /** Yat verilen aralıkta müsait mi? (yalnızca ONAYLI kayıtlar ve manuel bloklar kilitler) */
    public function isAvailable(Yacht $yacht, CarbonInterface $start, CarbonInterface $end, ?int $ignoreReservationId = null): bool
    {
        if (! $yacht->is_open) {
            return false;
        }

        return ! $this->conflicts($yacht, $start, $end, $ignoreReservationId)->isNotEmpty();
    }

    /** Çakışan blok kayıtları. */
    public function conflicts(Yacht $yacht, CarbonInterface $start, CarbonInterface $end, ?int $ignoreReservationId = null): Collection
    {
        $pad = $yacht->turnaround_minutes ?: 0;
        $paddedStart = $start->copy()->subMinutes($pad);
        $paddedEnd = $end->copy()->addMinutes($pad);

        return BlockedPeriod::query()
            ->where('yacht_id', $yacht->id)
            ->when($ignoreReservationId, fn ($q) => $q->where(function ($q) use ($ignoreReservationId) {
                $q->whereNull('reservation_id')->orWhere('reservation_id', '!=', $ignoreReservationId);
            }))
            ->overlapping($paddedStart, $paddedEnd)
            ->get();
    }

    /** Aynı aralığa gelmiş bekleyen talep sayısı — panelde "bu tarihte 2 talep var" uyarısı. */
    public function pendingRequestCount(Yacht $yacht, CarbonInterface $start, CarbonInterface $end, ?int $ignoreReservationId = null): int
    {
        return $yacht->reservations()
            ->pending()
            ->when($ignoreReservationId, fn ($q) => $q->where('id', '!=', $ignoreReservationId))
            ->overlapping($start, $end)
            ->count();
    }

    /** Takvim için kapalı aralıklar (yat detay sayfası müsaitlik takvimi). */
    public function blockedRanges(Yacht $yacht, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $yacht->blockedPeriods()
            ->overlapping($from, $to)
            ->orderBy('starts_at')
            ->get(['id', 'starts_at', 'ends_at', 'reason']);
    }

    /** Manuel blok (bakım, özel kullanım, site dışı rezervasyon). */
    public function block(Yacht $yacht, CarbonInterface $start, CarbonInterface $end, string $reason = 'manual', ?string $note = null): BlockedPeriod
    {
        return $yacht->blockedPeriods()->create([
            'starts_at' => $start,
            'ends_at' => $end,
            'reason' => $reason,
            'note' => $note,
        ]);
    }
}

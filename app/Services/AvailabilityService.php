<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\BlockedPeriod;
use App\Models\Yacht;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Kişi başı grup turu: günde tek sefer, kapasite dolana kadar bağımsız
 * müşteriler aynı tarihe rezervasyon yaptırabilir. "Talep kilitlemez, ONAY
 * kilitler" kuralı burada "onaylı rezervasyonlar kapasiteden düşer" olarak
 * uygulanır. Manuel blok (owner'ın bakım/özel kullanım için kapattığı gün)
 * günü tamamen kapatır.
 */
class AvailabilityService
{
    /** Bu tarihte istenen kişi sayısı için yer var mı? */
    public function isAvailable(Yacht $yacht, CarbonInterface $date, int $requestedGuests, ?int $ignoreReservationId = null): bool
    {
        if (! $yacht->is_open) {
            return false;
        }

        if ($this->isManuallyBlocked($yacht, $date)) {
            return false;
        }

        return $this->remainingSeats($yacht, $date, $ignoreReservationId) >= $requestedGuests;
    }

    /** Bu tarihte kalan koltuk sayısı. */
    public function remainingSeats(Yacht $yacht, CarbonInterface $date, ?int $ignoreReservationId = null): int
    {
        $capacity = (int) ($yacht->capacity ?? 0);

        $booked = $yacht->reservations()
            ->where('status', ReservationStatus::Approved)
            ->whereDate('starts_at', $date->toDateString())
            ->when($ignoreReservationId, fn ($q) => $q->where('id', '!=', $ignoreReservationId))
            ->get()
            ->sum(fn ($r) => $r->adults + $r->children);

        return max(0, $capacity - $booked);
    }

    /** Manuel olarak (bakım, özel kullanım) tamamen kapatılmış mı? */
    public function isManuallyBlocked(Yacht $yacht, CarbonInterface $date): bool
    {
        return $yacht->blockedPeriods()
            ->where('starts_at', '<', $date->copy()->endOfDay())
            ->where('ends_at', '>', $date->copy()->startOfDay())
            ->exists();
    }

    /** Aynı tarihe gelmiş bekleyen talep sayısı — panelde "bu tarihte 2 talep var" uyarısı. */
    public function pendingRequestCount(Yacht $yacht, CarbonInterface $date, ?int $ignoreReservationId = null): int
    {
        return $yacht->reservations()
            ->pending()
            ->whereDate('starts_at', $date->toDateString())
            ->when($ignoreReservationId, fn ($q) => $q->where('id', '!=', $ignoreReservationId))
            ->count();
    }

    /** Takvim için kapalı aralıklar (manuel bloklar — yat detay sayfası/owner takvimi). */
    public function blockedRanges(Yacht $yacht, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $yacht->blockedPeriods()
            ->overlapping($from, $to)
            ->orderBy('starts_at')
            ->get(['id', 'starts_at', 'ends_at', 'reason']);
    }

    /** Yat detay sayfası takvimi: her gün için kalan koltuk sayısı. */
    public function seatsForRange(Yacht $yacht, CarbonInterface $from, CarbonInterface $to): array
    {
        $seats = [];
        $cursor = $from->copy()->startOfDay();
        $stop = $to->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($stop)) {
            $seats[$cursor->toDateString()] = $this->isManuallyBlocked($yacht, $cursor)
                ? 0
                : $this->remainingSeats($yacht, $cursor);

            $cursor->addDay();
        }

        return $seats;
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

<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Collection as CollectionModel;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aylık hakediş/tahsilat dökümü.
 *
 * Para siteden geçmediği için buradaki iş **alacağı doğru hesaplamak ve takip
 * etmek**. Tutarlar yeniden hesaplanmaz: rezervasyon onaylandığında dondurulan
 * `commission_rate` / `commission_amount` toplanır.
 *
 * Dönemi belirleyen tarih `starts_at` (gidiş ayı) — hakediş, kiralamanın
 * gerçekleştiği aya yazılır.
 */
class CollectionService
{
    /**
     * Bir dönemi (yıl-ay) tüm yat sahipleri için üretir/günceller.
     *
     * @return \Illuminate\Support\Collection<int, CollectionModel>
     */
    public function build(int $year, int $month): \Illuminate\Support\Collection
    {
        $reservations = Reservation::query()
            ->where('status', ReservationStatus::Completed)
            ->whereYear('starts_at', $year)
            ->whereMonth('starts_at', $month)
            ->get()
            ->groupBy('owner_id');

        return $reservations->map(function ($group, $ownerId) use ($year, $month) {
            return $this->store((int) $ownerId, $year, $month, $group);
        })->values();
    }

    /** Tek yat sahibi için dönem kaydı. */
    public function buildForOwner(int $ownerId, int $year, int $month): ?CollectionModel
    {
        $group = Reservation::query()
            ->where('owner_id', $ownerId)
            ->where('status', ReservationStatus::Completed)
            ->whereYear('starts_at', $year)
            ->whereMonth('starts_at', $month)
            ->get();

        return $group->isEmpty() ? null : $this->store($ownerId, $year, $month, $group);
    }

    private function store(int $ownerId, int $year, int $month, $reservations): CollectionModel
    {
        return DB::transaction(function () use ($ownerId, $year, $month, $reservations) {
            /** @var CollectionModel $collection */
            $collection = CollectionModel::firstOrNew([
                'owner_id' => $ownerId,
                'year' => $year,
                'month' => $month,
            ]);

            // Tahsil edilmiş dönem yeniden hesaplanmaz — mutabakat bozulmasın.
            if ($collection->exists && $collection->status === 'collected') {
                return $collection;
            }

            $collection->fill([
                'revenue' => round((float) $reservations->sum('estimated_total'), 2),
                'commission' => round((float) $reservations->sum('commission_amount'), 2),
                'currency' => $reservations->first()->currency,
                'reservation_count' => $reservations->count(),
                'status' => $collection->status ?: 'pending',
            ])->save();

            Reservation::whereIn('id', $reservations->pluck('id'))
                ->update(['collection_id' => $collection->id]);

            return $collection;
        });
    }

    public function markCollected(CollectionModel $collection, ?string $invoiceNo = null, ?string $note = null): CollectionModel
    {
        $collection->fill([
            'status' => 'collected',
            'collected_at' => now()->toDateString(),
            'invoice_no' => $invoiceNo,
            'note' => $note ?: $collection->note,
        ])->save();

        return $collection;
    }

    public function markPending(CollectionModel $collection): CollectionModel
    {
        $collection->fill([
            'status' => 'pending',
            'collected_at' => null,
        ])->save();

        return $collection;
    }

    /** Kapanmış son ay — zamanlayıcı bunu üretir. */
    public function previousPeriod(): array
    {
        $date = Carbon::now()->subMonthNoOverflow();

        return [(int) $date->year, (int) $date->month];
    }
}

<?php

namespace App\Services;

use App\Enums\RentalUnit;
use App\Enums\ReservationStatus;
use App\Events\ReservationApproved;
use App\Events\ReservationCancelled;
use App\Events\ReservationRejected;
use App\Events\ReservationRequested;
use App\Models\BlockedPeriod;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Yacht;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Rezervasyon durum makinesi.
 *
 * Kritik kural: TALEP kilitlemez, ONAY kilitler. Onay bir veritabanı işlemi
 * içinde satır kilidiyle (lockForUpdate) yapılır; iki kişi aynı anda onaylasa
 * bile ikinci onay reddedilir.
 */
class ReservationService
{
    public function __construct(
        private PricingService $pricing,
        private AvailabilityService $availability,
        private CommissionService $commission,
    ) {}

    /** Adım 1 — talep. Takvimde hiçbir şey kapanmaz. */
    public function request(Yacht $yacht, array $data, array $extraIds = []): Reservation
    {
        $unit = $data['unit'] instanceof RentalUnit ? $data['unit'] : RentalUnit::from($data['unit']);
        $start = Carbon::parse($data['starts_at']);
        $end = Carbon::parse($data['ends_at']);

        if ($end->lessThanOrEqualTo($start)) {
            throw new RuntimeException('Bitiş tarihi başlangıçtan sonra olmalı.');
        }

        $quote = $this->pricing->quote($yacht, $unit, $start, $end, (int) ($data['guests'] ?? 1), $extraIds);

        $reservation = new Reservation;
        $reservation->fill([
            'yacht_id' => $yacht->id,
            'user_id' => $data['user_id'] ?? null,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'customer_whatsapp' => $data['customer_whatsapp'] ?? $data['customer_phone'],
            'customer_locale' => $data['customer_locale'] ?? app()->getLocale(),
            'unit' => $unit->value,
            'starts_at' => $start,
            'ends_at' => $end,
            'guests' => (int) ($data['guests'] ?? 1),
            'message' => $data['message'] ?? null,
        ]);

        $reservation->forceFill([
            'code' => Reservation::generateCode(),
            'owner_id' => $yacht->owner_id,
            'status' => ReservationStatus::Pending,
            'base_amount' => $quote['base'],
            'extras_amount' => $quote['extras'],
            'estimated_total' => $quote['total'],
            'currency' => $quote['currency'],
            'price_breakdown' => $quote['breakdown'],
            'selected_extras' => $extraIds,
            'source' => $data['source'] ?? 'web',
            'access_token' => Str::random(48),
        ])->save();

        $this->log($reservation, 'created', null, ReservationStatus::Pending, $data['channel'] ?? 'web', $data['ip'] ?? null);

        ReservationRequested::dispatch($reservation);

        return $reservation;
    }

    /** Adım 3 — onay. Tarih burada kapanır. */
    public function approve(Reservation $reservation, ?User $actor = null, string $channel = 'panel', ?string $ip = null): Reservation
    {
        $approved = DB::transaction(function () use ($reservation, $actor, $channel, $ip) {
            /** @var Reservation $fresh */
            $fresh = Reservation::whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status !== ReservationStatus::Pending) {
                throw new RuntimeException('Bu talep zaten yanıtlanmış (durum: '.$fresh->status->label().').');
            }

            $yacht = $fresh->yacht()->lockForUpdate()->firstOrFail();

            if (! $this->availability->isAvailable($yacht, $fresh->starts_at, $fresh->ends_at, $fresh->id)) {
                throw new RuntimeException('Bu tarih aralığı artık müsait değil.');
            }

            $from = $fresh->status;
            $this->commission->freeze($fresh);

            $fresh->forceFill([
                'status' => ReservationStatus::Approved,
                'approved_at' => now(),
                'responded_at' => $fresh->responded_at ?? now(),
            ])->save();

            $fresh->blockedPeriod()->create([
                'yacht_id' => $yacht->id,
                'starts_at' => $fresh->starts_at,
                'ends_at' => $fresh->ends_at,
                'reason' => 'reservation',
            ]);

            $this->log($fresh, 'approved', $from, ReservationStatus::Approved, $channel, $ip, $actor);

            return $fresh;
        });

        ReservationApproved::dispatch($approved);

        return $approved;
    }

    public function reject(Reservation $reservation, ?string $reason = null, ?User $actor = null, string $channel = 'panel', ?string $ip = null): Reservation
    {
        $rejected = DB::transaction(function () use ($reservation, $reason, $actor, $channel, $ip) {
            $fresh = Reservation::whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status !== ReservationStatus::Pending) {
                throw new RuntimeException('Bu talep zaten yanıtlanmış.');
            }

            $from = $fresh->status;

            $fresh->forceFill([
                'status' => ReservationStatus::Rejected,
                'reject_reason' => $reason,
                'responded_at' => now(),
            ])->save();

            $this->log($fresh, 'rejected', $from, ReservationStatus::Rejected, $channel, $ip, $actor, $reason);

            return $fresh;
        });

        ReservationRejected::dispatch($rejected);

        return $rejected;
    }

    /** İptal — kilit kalkar, tarih tekrar satışa açılır. */
    public function cancel(Reservation $reservation, ?string $reason = null, ?User $actor = null, string $channel = 'panel', ?string $ip = null): Reservation
    {
        $cancelled = DB::transaction(function () use ($reservation, $reason, $actor, $channel, $ip) {
            $fresh = Reservation::whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();

            if (in_array($fresh->status, [ReservationStatus::Cancelled, ReservationStatus::Completed], true)) {
                throw new RuntimeException('Bu rezervasyon iptal edilemez.');
            }

            $from = $fresh->status;

            $fresh->forceFill([
                'status' => ReservationStatus::Cancelled,
                'cancelled_at' => now(),
                'admin_note' => $reason ?: $fresh->admin_note,
            ])->save();

            BlockedPeriod::where('reservation_id', $fresh->id)->delete();

            $this->log($fresh, 'cancelled', $from, ReservationStatus::Cancelled, $channel, $ip, $actor, $reason);

            return $fresh;
        });

        ReservationCancelled::dispatch($cancelled);

        return $cancelled;
    }

    /** Adım 4 — gidiş tarihi geçince otomatik tamamlanır (zamanlayıcı çağırır). */
    public function complete(Reservation $reservation): Reservation
    {
        $from = $reservation->status;

        $reservation->forceFill([
            'status' => ReservationStatus::Completed,
            'completed_at' => now(),
        ])->save();

        $this->log($reservation, 'completed', $from, ReservationStatus::Completed, 'system');

        return $reservation;
    }

    public function markNoShow(Reservation $reservation, ?User $actor = null): Reservation
    {
        $from = $reservation->status;

        $reservation->forceFill(['status' => ReservationStatus::NoShow])->save();
        $this->log($reservation, 'no_show', $from, ReservationStatus::NoShow, 'panel', null, $actor);

        return $reservation;
    }

    public function log(
        Reservation $reservation,
        string $action,
        ?ReservationStatus $from = null,
        ?ReservationStatus $to = null,
        string $channel = 'panel',
        ?string $ip = null,
        ?User $actor = null,
        ?string $note = null,
    ): void {
        $reservation->logs()->create([
            'user_id' => $actor?->id ?? auth()->id(),
            'action' => $action,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'channel' => $channel,
            'ip' => $ip ?? request()->ip(),
            'note' => $note,
        ]);
    }
}

<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Events\ReservationApproved;
use App\Events\ReservationCancelled;
use App\Events\ReservationRejected;
use App\Events\ReservationRequested;
use App\Models\Consent;
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
    ) {}

    /** Adım 1 — talep. Kapasiteden hiçbir şey düşmez, sadece ONAY düşer. */
    public function request(Yacht $yacht, array $data, array $extraIds = []): Reservation
    {
        $date = Carbon::parse($data['date'])->startOfDay();
        $start = $date->copy()->setTimeFromTimeString((string) ($yacht->day_start ?? '09:00'));
        $end = $date->copy()->setTimeFromTimeString((string) ($yacht->day_end ?? '18:00'));

        if ($end->lessThanOrEqualTo($start)) {
            $end = $date->copy()->endOfDay();
        }

        $adults = (int) ($data['adults'] ?? 1);
        $children = (int) ($data['children'] ?? 0);

        $quote = $this->pricing->quote($yacht, $date, $adults, $children, $extraIds);

        $reservation = new Reservation;
        $reservation->fill([
            'yacht_id' => $yacht->id,
            'user_id' => $data['user_id'] ?? null,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'customer_whatsapp' => $data['customer_whatsapp'] ?? $data['customer_phone'],
            'customer_locale' => $data['customer_locale'] ?? app()->getLocale(),
            'unit' => 'day',
            'starts_at' => $start,
            'ends_at' => $end,
            'adults' => $adults,
            'children' => $children,
            'message' => $data['message'] ?? null,
        ]);

        /*
         * CRM kaydi: musteri e-postasina gore bulunur ya da acilir.
         * Uyelik olmadigi icin musteriyi birbirine baglayan tek sey bu.
         * forceFill icinde -- customer_id disaridan gelen bir alan degil,
         * yalnizca sunucu tarafinda yazilir.
         */
        $musteri = \App\Models\Customer::bulVeyaAc([
            'name' => $data['customer_name'],
            'email' => $data['customer_email'],
            'phone' => $data['customer_phone'],
            'whatsapp' => $data['customer_whatsapp'] ?? $data['customer_phone'],
            'locale' => $data['customer_locale'] ?? app()->getLocale(),
        ]);

        $reservation->forceFill([
            'code' => Reservation::generateCode(),
            'customer_id' => $musteri->id,
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

        // Riza kayitlari bildirimden ONCE yazilir: WhatsApp gonderimi acik riza
        // kaydini arar, sonra yazilirsa musteriye hicbir zaman mesaj gitmez.
        $this->recordConsents($reservation, $data);

        ReservationRequested::dispatch($reservation);

        return $reservation;
    }

    /**
     * KVKK ve WhatsApp acik riza kayitlari (tarih + IP ile).
     *
     * @param  array<int, string>  $data['consents']
     */
    private function recordConsents(Reservation $reservation, array $data): void
    {
        foreach ($data['consents'] ?? [] as $type) {
            Consent::create([
                'subject_type' => Reservation::class,
                'subject_id' => $reservation->id,
                'email' => $reservation->customer_email,
                'phone' => $reservation->customer_phone,
                'type' => $type,
                'ip' => $data['ip'] ?? null,
                'user_agent' => isset($data['user_agent']) ? substr((string) $data['user_agent'], 0, 255) : null,
            ]);
        }
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

            if (! $this->availability->isAvailable($yacht, $fresh->starts_at, $fresh->guests, $fresh->id)) {
                throw new RuntimeException('Bu tarihte yeterli kapasite kalmadı.');
            }

            $from = $fresh->status;

            $fresh->forceFill([
                'status' => ReservationStatus::Approved,
                'approved_at' => now(),
                'responded_at' => $fresh->responded_at ?? now(),
            ])->save();

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

<?php

namespace App\Console\Commands;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\NotificationService;
use App\Services\ReservationService;
use Illuminate\Console\Command;

/**
 * Rezervasyon otomasyonu — tek komut, dört iş. Zamanlayıcı saat başı çağırır.
 *
 * Yol haritası: "Ödeme olmayan bir modelde en büyük kayıp sebebi geç dönüştür;
 * sistem bunu kendi takip eder."
 */
class ProcessReservations extends Command
{
    protected $signature = 'reservations:process
                            {--only= : Tek adım çalıştır (remind|escalate|complete|trip)}';

    protected $description = 'Hatırlatma, admin devri, otomatik tamamlama ve gidiş hatırlatmasını işler';

    public function handle(NotificationService $notifications, ReservationService $reservations): int
    {
        $only = $this->option('only');

        if (! $only || $only === 'remind') {
            $this->remind($notifications);
        }

        if (! $only || $only === 'escalate') {
            $this->escalate($notifications);
        }

        if (! $only || $only === 'complete') {
            $this->completeFinished($reservations);
        }

        if (! $only || $only === 'trip') {
            $this->tripReminder($notifications);
        }

        return self::SUCCESS;
    }

    /** 4 saat yanıtsız kalan talepler için yat sahibine hatırlatma (bir kez). */
    private function remind(NotificationService $notifications): void
    {
        $threshold = now()->subHours((int) config('whatsapp.reminder_hours', 4));

        $due = Reservation::where('status', ReservationStatus::Pending)
            ->whereNull('reminded_at')
            ->where('created_at', '<=', $threshold)
            ->get();

        foreach ($due as $reservation) {
            $notifications->pendingReminder($reservation);
            $reservation->forceFill(['reminded_at' => now()])->save();
        }

        $this->components->info("Hatırlatma gönderilen talep: {$due->count()}");
    }

    /** 12 saat sonra admin devralır: kayıt "müdahale gerekiyor" olarak işaretlenir. */
    private function escalate(NotificationService $notifications): void
    {
        $threshold = now()->subHours((int) config('whatsapp.escalate_hours', 12));

        $due = Reservation::where('status', ReservationStatus::Pending)
            ->whereNull('escalated_at')
            ->where('created_at', '<=', $threshold)
            ->get();

        foreach ($due as $reservation) {
            $reservation->forceFill(['escalated_at' => now()])->save();
            $notifications->escalated($reservation);
        }

        $this->components->info("Admin'e devredilen talep: {$due->count()}");
    }

    /** Gidiş tarihi geçen onaylı rezervasyonlar tamamlanır (hakediş dökümüne düşer). */
    private function completeFinished(ReservationService $reservations): void
    {
        $due = Reservation::where('status', ReservationStatus::Approved)
            ->where('ends_at', '<', now())
            ->get();

        foreach ($due as $reservation) {
            $reservations->complete($reservation);
        }

        $this->components->info("Tamamlanan rezervasyon: {$due->count()}");
    }

    /** Kalkıştan 3 gün önce müşteriye hatırlatma (bir kez). */
    private function tripReminder(NotificationService $notifications): void
    {
        $due = Reservation::where('status', ReservationStatus::Approved)
            ->whereBetween('starts_at', [now(), now()->addDays(3)])
            ->whereDoesntHave('messages', fn ($q) => $q->where('template', 'trip_reminder_customer'))
            ->get();

        foreach ($due as $reservation) {
            $notifications->tripReminder($reservation);
        }

        $this->components->info("Gidiş hatırlatması: {$due->count()}");
    }
}

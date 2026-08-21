<?php

namespace App\Services;

use App\Mail\ReservationMail;
use App\Models\MessageLog;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tek bildirim kapısı. Her gönderim `message_logs`'a yazılır; hata akışı bozmaz
 * (rezervasyon kaydedildi ama mail gitmedi durumunda kullanıcı hata görmez,
 * admin mesaj kaydı ekranında görür).
 *
 * WhatsApp kanalı Faz 4'te buraya eklenecek: aynı şablon anahtarları, aynı log.
 */
class NotificationService
{
    /** Adım 2 — talep geldi: müşteri + yat sahibi + admin. */
    public function reservationRequested(Reservation $reservation): void
    {
        $this->toCustomer($reservation, 'request_received_customer');
        $this->toOwner($reservation, 'request_new_owner');
        $this->toAdmins($reservation, 'request_new_admin');
    }

    public function reservationApproved(Reservation $reservation): void
    {
        $this->toCustomer($reservation, 'approved_customer');
        $this->toOwner($reservation, 'approved_owner');
    }

    public function reservationRejected(Reservation $reservation): void
    {
        $this->toCustomer($reservation, 'rejected_customer');
    }

    public function reservationCancelled(Reservation $reservation): void
    {
        $this->toCustomer($reservation, 'cancelled_customer');
        $this->toOwner($reservation, 'cancelled_owner');
    }

    /** 4 saat yanıtsız kalan talep. */
    public function pendingReminder(Reservation $reservation): void
    {
        $this->toOwner($reservation, 'pending_reminder_owner');
    }

    /** 12 saat sonra admin devralır. */
    public function escalated(Reservation $reservation): void
    {
        $this->toAdmins($reservation, 'escalated_admin');
    }

    /** Kalkıştan 3 gün önce. */
    public function tripReminder(Reservation $reservation): void
    {
        $this->toCustomer($reservation, 'trip_reminder_customer');
    }

    private function toCustomer(Reservation $reservation, string $template): void
    {
        $this->send(
            $reservation,
            $template,
            $reservation->customer_email,
            $reservation->customer_locale ?: config('app.locale'),
            $reservation->user
        );
    }

    private function toOwner(Reservation $reservation, string $template): void
    {
        $owner = $reservation->owner;

        if (! $owner?->email) {
            return;
        }

        $this->send($reservation, $template, $owner->email, $owner->locale ?: config('app.locale'), $owner);
    }

    private function toAdmins(Reservation $reservation, string $template): void
    {
        $recipients = User::query()
            ->where('role', \App\Enums\UserRole::Admin)
            ->where('is_active', true)
            ->get();

        if ($recipients->isEmpty() && $fallback = setting('site_email')) {
            $this->send($reservation, $template, $fallback, config('app.locale'));

            return;
        }

        foreach ($recipients as $admin) {
            $this->send($reservation, $template, $admin->email, $admin->locale ?: config('app.locale'), $admin);
        }
    }

    private function send(
        Reservation $reservation,
        string $template,
        string $recipient,
        string $locale,
        ?User $user = null,
    ): void {
        $log = MessageLog::create([
            'related_type' => Reservation::class,
            'related_id' => $reservation->id,
            'channel' => 'mail',
            'template' => $template,
            'locale' => $locale,
            'recipient' => $recipient,
            'user_id' => $user?->id,
            'status' => 'queued',
        ]);

        try {
            Mail::to($recipient)
                ->locale($locale)
                ->send(new ReservationMail($reservation, $template));

            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);

            Log::error('Bildirim gönderilemedi', [
                'template' => $template,
                'recipient' => $recipient,
                'reservation' => $reservation->code,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

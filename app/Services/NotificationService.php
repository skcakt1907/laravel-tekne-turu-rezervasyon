<?php

namespace App\Services;

use App\Mail\ReservationMail;
use App\Models\Consent;
use App\Models\MessageLog;
use App\Models\Reservation;
use App\Models\User;
use App\Services\WhatsApp\TemplateRegistry;
use App\Services\WhatsApp\WhatsAppClient;
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
    public function __construct(private WhatsAppClient $whatsapp) {}

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
        $locale = $reservation->customer_locale ?: config('app.locale');

        $this->send($reservation, $template, $reservation->customer_email, $locale, $reservation->user);
        $this->sendWhatsApp(
            $reservation,
            $template,
            $reservation->customer_whatsapp ?: $reservation->customer_phone,
            $locale,
            null
        );
    }

    private function toOwner(Reservation $reservation, string $template): void
    {
        $owner = $reservation->owner;

        if (! $owner?->email) {
            return;
        }

        $locale = $owner->locale ?: config('app.locale');

        $this->send($reservation, $template, $owner->email, $locale, $owner);
        $this->sendWhatsApp($reservation, $template, $owner->notificationPhone(), $locale, $owner);
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
            $locale = $admin->locale ?: config('app.locale');

            $this->send($reservation, $template, $admin->email, $locale, $admin);
            $this->sendWhatsApp($reservation, $template, $admin->notificationPhone(), $locale, $admin);
        }
    }

    /**
     * WhatsApp kanalı. Üçü de sağlanmadıkça gönderilmez: kanal açık +
     * Meta'da karşılığı olan şablon + (müşteriyse) açık rıza kaydı.
     */
    private function sendWhatsApp(
        Reservation $reservation,
        string $template,
        ?string $phone,
        string $locale,
        ?User $user = null,
    ): void {
        if (blank($phone) || ! $this->whatsapp->enabled() || ! TemplateRegistry::has($template)) {
            return;
        }

        if (! $user && ! $this->hasWhatsAppConsent($reservation)) {
            return;
        }

        $log = MessageLog::create([
            'related_type' => Reservation::class,
            'related_id' => $reservation->id,
            'channel' => 'whatsapp',
            'template' => $template,
            'locale' => $locale,
            'recipient' => $phone,
            'user_id' => $user?->id,
            'status' => 'queued',
        ]);

        try {
            $messageId = $this->whatsapp->sendTemplate(
                $phone,
                TemplateRegistry::metaName($template),
                $locale,
                TemplateRegistry::paramsFor($template, $reservation, $locale),
            );

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
                'provider_message_id' => $messageId,
            ]);
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);

            Log::error('WhatsApp gönderilemedi', [
                'template' => $template,
                'reservation' => $reservation->code,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Müşteriye WhatsApp yalnızca formda açık rıza verildiyse gider (Meta + KVKK). */
    private function hasWhatsAppConsent(Reservation $reservation): bool
    {
        return Consent::where('subject_type', Reservation::class)
            ->where('subject_id', $reservation->id)
            ->where('type', Consent::TYPE_WHATSAPP)
            ->where('granted', true)
            ->exists();
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

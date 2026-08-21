<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tüm rezervasyon e-postaları tek sınıftan çıkar; ayrım `template` anahtarında.
 * Anahtarlar config/whatsapp.php'deki şablon adlarıyla birebir aynı — Faz 4'te
 * WhatsApp kanalı aynı anahtarları kullanacak.
 */
class ReservationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation,
        public string $template,
        public array $extra = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.subjects.'.$this->template, [
                'code' => $this->reservation->code,
                'yacht' => $this->reservation->yacht->getTranslation('name', app()->getLocale()),
                'site' => setting('site_name', config('app.name')),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservations.'.$this->template,
            with: [
                'reservation' => $this->reservation,
                'yacht' => $this->reservation->yacht,
                'extra' => $this->extra,
            ],
        );
    }
}

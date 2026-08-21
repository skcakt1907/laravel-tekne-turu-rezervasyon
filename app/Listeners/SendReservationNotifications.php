<?php

namespace App\Listeners;

use App\Events\ReservationApproved;
use App\Events\ReservationCancelled;
use App\Events\ReservationRejected;
use App\Events\ReservationRequested;
use App\Services\NotificationService;

/**
 * Rezervasyon olaylarini bildirim kanallarina baglar.
 * Faz 4'te WhatsApp ayni servise eklenecek; burasi degismeyecek.
 *
 * DIKKAT: metot adlari bilerek "on*" — Laravel app/Listeners altindaki
 * "handle*" metotlarini otomatik kesfedip kaydeder, subscribe() ile birlikte
 * her bildirim iki kez giderdi.
 */
class SendReservationNotifications
{
    public function __construct(private NotificationService $notifications) {}

    public function onRequested(ReservationRequested $event): void
    {
        $this->notifications->reservationRequested($event->reservation);
    }

    public function onApproved(ReservationApproved $event): void
    {
        $this->notifications->reservationApproved($event->reservation);
    }

    public function onRejected(ReservationRejected $event): void
    {
        $this->notifications->reservationRejected($event->reservation);
    }

    public function onCancelled(ReservationCancelled $event): void
    {
        $this->notifications->reservationCancelled($event->reservation);
    }

    /** @return array<class-string, string> */
    public function subscribe(): array
    {
        return [
            ReservationRequested::class => 'onRequested',
            ReservationApproved::class => 'onApproved',
            ReservationRejected::class => 'onRejected',
            ReservationCancelled::class => 'onCancelled',
        ];
    }
}

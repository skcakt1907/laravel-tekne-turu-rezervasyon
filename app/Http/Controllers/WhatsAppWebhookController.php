<?php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use App\Services\WhatsApp\TemplateRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Meta Cloud API webhook'u — akışın kalbi.
 *
 * İki tür olay gelir:
 *  - `messages`: yat sahibinin bastığı Onayla/Reddet butonu (hızlı yanıt)
 *  - `statuses`: gönderdiğimiz mesajın teslim/okundu/hata durumu
 *
 * Rota CSRF'den muaf (bootstrap/app.php). Meta imzayı `X-Hub-Signature-256`
 * ile gönderir; `whatsapp.app_secret` tanımlı olmalı, aksi halde istek reddedilir.
 */
class WhatsAppWebhookController extends Controller
{
    public function __construct(private ReservationService $reservations) {}

    /** Meta'nın webhook doğrulama çağrısı (bir kez, kurulum sırasında). */
    public function verify(Request $request)
    {
        $token = config('whatsapp.verify_token');

        if (blank($token) || $request->query('hub_verify_token') !== $token) {
            abort(403);
        }

        return response((string) $request->query('hub_challenge'), 200)
            ->header('Content-Type', 'text/plain');
    }

    public function handle(Request $request)
    {
        if (! $this->hasValidSignature($request)) {
            Log::warning('WhatsApp webhook: imza dogrulamasi basarisiz', ['ip' => $request->ip()]);

            abort(403);
        }

        // Meta yeniden denemesin diye her durumda 200 döneriz; hatayı kendimiz loglarız.
        try {
            foreach ($request->input('entry', []) as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];

                    foreach ($value['statuses'] ?? [] as $status) {
                        $this->recordStatus($status);
                    }

                    foreach ($value['messages'] ?? [] as $message) {
                        $this->handleMessage($message);
                    }
                }
            }
        } catch (Throwable $e) {
            Log::error('WhatsApp webhook hatası', ['error' => $e->getMessage()]);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Meta imzasi (X-Hub-Signature-256: sha256=<hmac>) dogrulanir. app_secret
     * tanimli degilse istek reddedilir — imzasiz webhook kabul edilmez.
     */
    private function hasValidSignature(Request $request): bool
    {
        $secret = config('whatsapp.app_secret');
        $header = (string) $request->header('X-Hub-Signature-256', '');

        if (blank($secret) || ! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, substr($header, 7));
    }

    /** Teslim/okundu/hata bilgisini mesaj kaydına işler. */
    private function recordStatus(array $status): void
    {
        $log = MessageLog::where('provider_message_id', $status['id'] ?? '')->first();

        if (! $log) {
            return;
        }

        $attributes = match ($status['status'] ?? '') {
            'delivered' => ['status' => 'delivered', 'delivered_at' => now()],
            'read' => ['status' => 'read', 'read_at' => now()],
            'failed' => [
                'status' => 'failed',
                'error' => $status['errors'][0]['title'] ?? 'Bilinmeyen hata',
            ],
            'sent' => ['status' => 'sent', 'sent_at' => now()],
            default => null,
        };

        if ($attributes) {
            $log->update($attributes);
        }
    }

    /** Yat sahibinin buton yanıtı — rezervasyonu panelsiz onaylar/reddeder. */
    private function handleMessage(array $message): void
    {
        if (($message['type'] ?? '') !== 'button') {
            return; // serbest metin: Chatwoot tarafında karşılanır
        }

        $payload = $message['button']['payload'] ?? $message['button']['text'] ?? '';
        $contextId = $message['context']['id'] ?? null;

        $reservation = $this->resolveReservation($contextId, $message['from'] ?? null);

        if (! $reservation) {
            Log::warning('WhatsApp buton yanıtı eşleşmedi', ['context' => $contextId]);

            return;
        }

        $decision = $this->decisionFrom($payload);

        if (! $decision) {
            return;
        }

        // Butona basan yonetici. Bulunamazsa islem yine yapilir, kayda
        // yalnizca kanal ('whatsapp') yazilir -- numara taninmadi diye
        // onayi dusurmek yoneticiyi panele mahkum ederdi.
        $yanitlayan = $this->yoneticiBul($message['from'] ?? null);

        try {
            if ($decision === 'approve') {
                $this->reservations->approve($reservation, $yanitlayan, 'whatsapp');
            } else {
                $this->reservations->reject($reservation, null, $yanitlayan, 'whatsapp');
            }
        } catch (Throwable $e) {
            // Tarih kapanmışsa veya talep zaten yanıtlanmışsa: sessizce logla.
            Log::info('WhatsApp buton yanıtı uygulanamadı', [
                'reservation' => $reservation->code,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    /** Önce mesaj kaydı üzerinden, olmazsa numaranın bekleyen tek talebinden. */
    private function resolveReservation(?string $contextId, ?string $from): ?Reservation
    {
        if ($contextId) {
            $log = MessageLog::where('provider_message_id', $contextId)
                ->where('related_type', Reservation::class)
                ->first();

            if ($log?->related_id) {
                return Reservation::find($log->related_id);
            }
        }

        if (! $from) {
            return null;
        }

        // Numara bir yoneticiye ait degilse hicbir sey yapilmaz.
        if (! $this->yoneticiBul($from)) {
            return null;
        }

        /*
         * Baglam kaybolmussa (Meta context.id gondermediyse) yalnizca TEK
         * bir bekleyen talep varsa ona uygulanir. Birden fazlaysa hangisi
         * oldugu bilinemez; yanlis talebi onaylamaktansa hicbir sey
         * yapmamak dogru.
         */
        $pending = Reservation::pending()->latest('id')->get();

        return $pending->count() === 1 ? $pending->first() : null;
    }

    /**
     * WhatsApp numarasindan yoneticiyi bulur.
     *
     * Tur sahibi kavrami kaldirildi (site tek firma); onayi verebilecek
     * kisi yoneticidir. Karsilastirma +, bosluk ve tire temizlenerek
     * yapilir -- panele "+90 532 ..." girilmis olabilir, Meta ise
     * "90532..." gonderir.
     */
    private function yoneticiBul(?string $from): ?User
    {
        if (blank($from)) {
            return null;
        }

        return User::where('role', 'admin')
            ->whereRaw(
                "replace(replace(replace(coalesce(whatsapp_no, phone), '+', ''), ' ', ''), '-', '') = ?",
                [preg_replace('/\D+/', '', $from)]
            )
            ->first();
    }

    private function decisionFrom(string $payload): ?string
    {
        $normalized = mb_strtoupper(trim($payload), 'UTF-8');

        return match (true) {
            str_contains($normalized, TemplateRegistry::BUTTON_APPROVE), str_contains($normalized, 'APPROVE') => 'approve',
            str_contains($normalized, TemplateRegistry::BUTTON_REJECT), str_contains($normalized, 'DECLINE') => 'reject',
            default => null,
        };
    }
}

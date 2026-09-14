<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Consent;
use App\Models\Reservation;
use App\Models\Yacht;
use App\Services\AvailabilityService;
use App\Services\NotificationService;
use App\Services\ReservationService;
use App\Support\FormKorumasi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Faz 3 / Faz 6. Görünümler Faz 2'de tasarımla birlikte yazılacak;
 * buradaki iş mantığı (doğrulama, rıza kaydı, güvenli bağlantı) hazır.
 */
class ReservationController extends Controller
{
    public function __construct(
        private ReservationService $reservations,
        private AvailabilityService $availability,
        private NotificationService $notifications,
    ) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'yacht_id' => ['required', 'exists:yachts,id'],
            'date' => ['required', 'date', 'after:today'],
            'adults' => ['required', 'integer', 'min:1', 'max:100'],
            'children' => ['nullable', 'integer', 'min:0', 'max:100'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:190'],
            'customer_phone' => ['required', 'string', 'max:32'],
            'customer_whatsapp' => ['nullable', 'string', 'max:32'],
            'message' => ['nullable', 'string', 'max:2000'],
            'extras' => ['array'],
            'extras.*' => ['integer'],
            'kvkk' => ['accepted'],
            'whatsapp_consent' => ['accepted'], // Meta + KVKK zorunlu açık rıza
        ]);

        /*
         * SAYI SINIRI — aynı IP saatte 5 rezervasyon.
         *
         * İletişim formundan yüksek tutuldu: bir ailenin aynı oturumda
         * iki-üç tur için form doldurması normal. Beşten fazlası değil.
         */
        $anahtar = 'rezervasyon:'.$request->ip();

        if (RateLimiter::tooManyAttempts($anahtar, 5)) {
            throw ValidationException::withMessages([
                'customer_email' => __('site.booking.too_many', [
                    'dakika' => max(1, (int) ceil(RateLimiter::availableIn($anahtar) / 60)),
                ]),
            ]);
        }

        RateLimiter::hit($anahtar, 3600);

        /*
         * BOT KORUMASI.
         *
         * İletişim formundan farklı olarak burada sessizce "başarılı"
         * diyemiyoruz: akış, oluşturulan rezervasyonun sayfasına
         * yönlendiriyor, ortada gösterilecek bir kayıt yok. Bunun yerine
         * nötr bir hata veriliyor — bot zaten okumuyor; formu 12 saatten
         * uzun açık bırakmış gerçek bir kullanıcı ise ne yapacağını
         * buradan anlıyor.
         */
        if (FormKorumasi::botMu($request, (string) ($data['message'] ?? ''))) {
            throw ValidationException::withMessages([
                'customer_email' => __('site.booking.retry'),
            ]);
        }

        $yacht = Yacht::bookable()->findOrFail($data['yacht_id']);
        $date = Carbon::parse($data['date'])->startOfDay();
        $adults = (int) $data['adults'];
        $children = (int) ($data['children'] ?? 0);

        // Talep kilitlemez ama kapasitesi dolmuş tarihe talep de almayalım.
        if (! $this->availability->isAvailable($yacht, $date, $adults + $children)) {
            throw ValidationException::withMessages([
                'date' => 'Seçtiğiniz tarihte yeterli kapasite kalmadı.',
            ]);
        }

        $reservation = $this->reservations->request($yacht, [
            'user_id' => $request->user()?->id,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'customer_whatsapp' => $data['customer_whatsapp'] ?? null,
            'customer_locale' => app()->getLocale(),
            'date' => $date,
            'adults' => $adults,
            'children' => $children,
            'message' => $data['message'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'consents' => [Consent::TYPE_KVKK, Consent::TYPE_WHATSAPP],
        ], $data['extras'] ?? []);

        return redirect()
            ->to(lroute('reservation.show', ['code' => $reservation->code]).'?token='.$reservation->access_token)
            ->with('status', __('site.booking.title').': '.$reservation->code);
    }

    public function lookupForm()
    {
        return view('reservations.lookup');
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email'],
        ]);

        $reservation = Reservation::where('code', $data['code'])
            ->where('customer_email', $data['email'])
            ->first();

        if (! $reservation) {
            throw ValidationException::withMessages(['code' => 'Rezervasyon bulunamadı.']);
        }

        return redirect()->to(
            lroute('reservation.show', ['code' => $reservation->code]).'?token='.$reservation->access_token
        );
    }

    public function show(Request $request, string $code)
    {
        $reservation = $this->resolve($code, $request->query('token'));

        return view('reservations.show', compact('reservation'));
    }

    /**
     * Müşteri iptal talebi. Müşteri rezervasyonu kendisi iptal EDEMEZ; talebi
     * yat sahibine iletilir, kararı o verir (yol haritası bölüm 08).
     */
    public function requestCancellation(Request $request, string $code)
    {
        $reservation = $this->resolve($code, $request->input('token', $request->query('token')));

        abort_unless($reservation->canRequestCancellation(), 410, 'Bu rezervasyon için iptal talebi gönderilemez.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $reservation->forceFill([
            'cancel_requested_at' => now(),
            'cancel_request_reason' => $data['reason'],
        ])->save();

        $this->reservations->log(
            $reservation,
            'cancel_requested',
            $reservation->status,
            $reservation->status,
            'web',
            $request->ip(),
            null,
            $data['reason']
        );

        $this->notifications->cancellationRequested($reservation);

        return back()->with('status', __('site.booking.cancel_sent'));
    }

    /** WhatsApp mesajındaki güvenli bağlantı — şifresiz onay ekranı. */
    public function ownerDecision(string $code, string $token)
    {
        $reservation = $this->resolve($code, $token);

        abort_unless($reservation->status === ReservationStatus::Pending, 410, 'Bu talep zaten yanıtlanmış.');

        return view('reservations.decision', compact('reservation'));
    }

    public function ownerDecide(Request $request, string $code, string $token)
    {
        $reservation = $this->resolve($code, $token);

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['decision'] === 'approve') {
            $this->reservations->approve($reservation, $reservation->owner, 'link', $request->ip());
        } else {
            $this->reservations->reject($reservation, $data['reason'] ?? null, $reservation->owner, 'link', $request->ip());
        }

        return back()->with('status', 'Yanıtınız kaydedildi.');
    }

    private function resolve(string $code, ?string $token): Reservation
    {
        abort_if(blank($token), 403);

        $reservation = Reservation::where('code', $code)->firstOrFail();

        abort_unless(hash_equals((string) $reservation->access_token, $token), 403);

        return $reservation;
    }
}

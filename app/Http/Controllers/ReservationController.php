<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Consent;
use App\Models\Reservation;
use App\Models\Yacht;
use App\Services\AvailabilityService;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
    ) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'yacht_id' => ['required', 'exists:yachts,id'],
            'unit' => ['required', 'in:hour,day,week'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'guests' => ['required', 'integer', 'min:1', 'max:100'],
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

        $yacht = Yacht::bookable()->findOrFail($data['yacht_id']);
        $start = Carbon::parse($data['starts_at']);
        $end = Carbon::parse($data['ends_at']);

        // Talep kilitlemez ama dolu tarihe talep de almayalım.
        if (! $this->availability->isAvailable($yacht, $start, $end)) {
            throw ValidationException::withMessages([
                'starts_at' => 'Seçtiğiniz tarih aralığı müsait değil.',
            ]);
        }

        $reservation = $this->reservations->request($yacht, [
            'user_id' => $request->user()?->id,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'customer_whatsapp' => $data['customer_whatsapp'] ?? null,
            'customer_locale' => app()->getLocale(),
            'unit' => $data['unit'],
            'starts_at' => $start,
            'ends_at' => $end,
            'guests' => $data['guests'],
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

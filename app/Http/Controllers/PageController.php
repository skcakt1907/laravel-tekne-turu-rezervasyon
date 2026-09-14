<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Page;
use App\Support\FormKorumasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('pages.show', compact('page'));
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function contactStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        /*
         * SAYI SINIRI -- ayni IP saatte 3 mesaj.
         *
         * Bot koruması aşılsa bile toplu gönderimi burası keser. Gerçek
         * bir müşteri saatte üçten fazla mesaj yazmaz; yazarsa da ilk
         * üçü zaten elimizde.
         */
        $anahtar = 'iletisim:' . $request->ip();

        if (RateLimiter::tooManyAttempts($anahtar, 3)) {
            throw ValidationException::withMessages([
                'message' => __('site.contact.too_many', [
                    'dakika' => max(1, (int) ceil(RateLimiter::availableIn($anahtar) / 60)),
                ]),
            ]);
        }

        RateLimiter::hit($anahtar, 3600);

        /*
         * Bot yakalandiginda kullaniciya BASARILI cevabi donuyoruz ama
         * kayit acilmiyor. Hata gosterirsek bot hangi alanin ele
         * verdigini deneyerek bulur.
         */
        if (FormKorumasi::botMu($request, $data['message'] . ' ' . ($data['subject'] ?? ''))) {
            return back()->with('status', __('site.contact.sent'));
        }

        ContactMessage::create($data + ['ip' => $request->ip()]);

        return back()->with('status', __('site.contact.sent'));
    }
}

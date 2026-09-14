<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\YachtController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site onyuzu
|--------------------------------------------------------------------------
| Turkce oneksiz, Ingilizce /en/ onekiyle (yol haritasi bolum 10).
| Panel rotalari Filament tarafindan uretilir: /yonetim
*/

$site = function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/turlar', [YachtController::class, 'index'])->name('tours.index');
    Route::get('/tur/{slug}', [YachtController::class, 'show'])->name('tours.show');

    // Rezervasyon talebi
    Route::post('/rezervasyon-talebi', [ReservationController::class, 'store'])->name('reservation.store');

    // Musteri ekrani (kod + e-posta veya guvenli baglanti)
    Route::get('/rezervasyon-sorgula', [ReservationController::class, 'lookupForm'])->name('reservation.lookup');
    Route::post('/rezervasyon-sorgula', [ReservationController::class, 'lookup'])->name('reservation.lookup.submit');
    Route::get('/rezervasyon/{code}', [ReservationController::class, 'show'])->name('reservation.show');

    // Yat sahibinin sifresiz onay ekrani (WhatsApp yedek yolu)
    Route::get('/onay/{code}/{token}', [ReservationController::class, 'ownerDecision'])->name('reservation.decision');
    Route::post('/onay/{code}/{token}', [ReservationController::class, 'ownerDecide'])->name('reservation.decide');

    /*
     * UYELIK YOK. Musteri hesap acmadan rezervasyon yapiyor; kim oldugu
     * `customers` tablosunda tutuluyor (bkz. CustomerResource). Rezervasyonunu
     * takip etmek icin giris degil, asagidaki `reservation.lookup` ekrani
     * kullaniliyor -- kod + e-posta ile.
     */

    // Musteri iptal talebi (karari yat sahibi verir)
    Route::post('/rezervasyon/{code}/iptal-talebi', [ReservationController::class, 'requestCancellation'])
        ->name('reservation.cancel-request');

    // Kurumsal
    Route::get('/iletisim', [PageController::class, 'contact'])->name('contact');
    Route::post('/iletisim', [PageController::class, 'contactStore'])->name('contact.store');
    Route::get('/sayfa/{slug}', [PageController::class, 'show'])->name('pages.show');
};

// Varsayilan dil (TR) - oneksiz
Route::middleware('setlocale')->group($site);

// Diger diller - literal onekle ayni rotalar (/en/...). Rota parametresi EKLENMEZ;
// {locale} yer tutucu kullanilsaydi controller imzalarina fazladan arguman gecerdi.
foreach (array_slice(array_keys(config('yacht.locales')), 1) as $locale) {
    Route::prefix($locale)
        ->name($locale.'.')
        ->middleware('setlocale')
        ->group($site);
}

// Site haritasi - dil onekinden bagimsiz, tum dilleri icerir
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

/*
| Meta Cloud API webhook'u — dil onekinden ve CSRF'den bagimsiz.
| Meta'ya verilecek adres: https://alan-adi/webhook/whatsapp
*/
Route::get('webhook/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('webhook/whatsapp', [WhatsAppWebhookController::class, 'handle']);

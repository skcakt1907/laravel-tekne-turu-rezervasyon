<?php

use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site onyuzu
|--------------------------------------------------------------------------
| Turkce oneksiz, Ingilizce /en/ onekiyle (yol haritasi bolum 10).
| Panel rotalari Filament tarafindan uretilir: /yonetim ve /yat-sahibi
|
| Faz 2 (site onyuzu) ve Faz 6 (musteri ekrani) burada tamamlanacak.
*/

Route::middleware('setlocale')->group(function () {
    Route::view('/', 'placeholder')->name('home');

    // Faz 2 - yat listesi ve detay
    Route::view('/yatlar', 'placeholder')->name('yachts.index');
    Route::view('/yat/{slug}', 'placeholder')->name('yachts.show');
    Route::view('/liman/{slug}', 'placeholder')->name('locations.show');

    // Faz 3 - rezervasyon talebi
    Route::post('/rezervasyon-talebi', [ReservationController::class, 'store'])->name('reservation.store');

    // Faz 6 - musteri ekrani (kod + e-posta veya guvenli baglanti)
    Route::get('/rezervasyon-sorgula', [ReservationController::class, 'lookupForm'])->name('reservation.lookup');
    Route::post('/rezervasyon-sorgula', [ReservationController::class, 'lookup'])->name('reservation.lookup.submit');
    Route::get('/rezervasyon/{code}', [ReservationController::class, 'show'])->name('reservation.show');

    // Yat sahibinin sifresiz onay ekrani (WhatsApp yedek yolu)
    Route::get('/onay/{code}/{token}', [ReservationController::class, 'ownerDecision'])->name('reservation.decision');
    Route::post('/onay/{code}/{token}', [ReservationController::class, 'ownerDecide'])->name('reservation.decide');

    // Kurumsal sayfalar
    Route::view('/sayfa/{slug}', 'placeholder')->name('pages.show');
    Route::view('/iletisim', 'placeholder')->name('contact');
});

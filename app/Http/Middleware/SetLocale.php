<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dili YALNIZCA adres belirler: /en/... -> en, oneksiz -> varsayilan (tr).
 *
 * Oturumdan hatirlama bilerek yok; ayni adresin iki farkli dilde cevap vermesi
 * hem arama motoru (hreflang/canonical catismasi) hem paylasilan link acisindan
 * yanlis olurdu.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('yacht.locales', ['tr' => []]));
        $default = $supported[0] ?? 'tr';

        $segment = $request->segment(1);
        $locale = in_array($segment, $supported, true) ? $segment : $default;

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}

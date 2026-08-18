<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dil secimi: /en/ oneki > oturum > varsayilan (tr).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('yacht.locales', ['tr' => []]));
        $segment = $request->segment(1);

        if (in_array($segment, $supported, true)) {
            $locale = $segment;
        } else {
            $locale = session('locale', config('app.locale'));
        }

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }
}

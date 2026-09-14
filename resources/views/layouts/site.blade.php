<!doctype html>
<html lang="{{ app()->getLocale() }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', setting('site_name', config('app.name')))</title>
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <meta name="description" content="@yield('meta_description', setting('site_description', ''))">
    <link rel="canonical" href="{{ url()->current() }}">

    @foreach (config('yacht.locales') as $code => $cfg)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ locale_url($code) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ locale_url(array_key_first(config('yacht.locales'))) }}">

    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', setting('site_name', config('app.name')))">
    <meta property="og:description" content="@yield('meta_description', '')">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif

    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @include('partials.schema-organization')
    @stack('head')
</head>
<body class="min-h-screen font-sans antialiased">

<a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 btn btn-brass">
    {{ __('site.nav.skip') }}
</a>

@php
    $transparentHeader = trim($__env->yieldContent('header_style')) === 'transparent';
@endphp

<header x-data="{ open: false, scrolled: false }"
        x-init="scrolled = window.scrollY > 8; window.addEventListener('scroll', () => scrolled = window.scrollY > 8)"
        :class="(scrolled || open || {{ $transparentHeader ? 'false' : 'true' }})
            ? 'bg-white/95 backdrop-blur border-sea-200 text-sea-900'
            : 'bg-transparent border-transparent text-white'"
        class="fixed inset-x-0 top-0 z-40 border-b transition-colors duration-300">
    <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6">
        <a href="{{ lroute('home') }}" class="flex items-center gap-2 font-serif text-lg font-bold">
            <i class="bi bi-life-preserver text-brass-500"></i>
            {{ setting('site_name', config('app.name')) }}
        </a>

        <nav class="ml-auto hidden items-center gap-1 lg:flex">
            <a href="{{ lroute('tours.index') }}"
               class="rounded-lg px-3 py-2 text-sm font-medium transition hover:text-brass-500 {{ request()->routeIs('*tours.*') ? 'text-brass-500' : '' }}">
                {{ __('site.nav.yachts') }}
            </a>

            <a href="{{ lroute('reservation.lookup') }}"
               class="rounded-lg px-3 py-2 text-sm font-medium transition hover:text-brass-500">
                {{ __('site.nav.lookup') }}
            </a>
            <a href="{{ lroute('contact') }}"
               class="rounded-lg px-3 py-2 text-sm font-medium transition hover:text-brass-500">
                {{ __('site.nav.contact') }}
            </a>

            {{-- İLETİŞİM — sitede ödeme alınmadığı için insanlar arayarak
                 teyit etmek istiyor; numara menüde, aramadan bulunur olmalı.
                 Ayar boşsa hiç basılmıyor, boş bir ikon görünmesin diye. --}}
            @if (setting('site_phone'))
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', setting('site_phone')) }}"
                   class="ml-2 rounded-lg px-3 py-2 text-sm font-semibold text-brass-500 transition hover:text-brass-400">
                    <i class="bi bi-telephone-fill mr-1"></i>{{ setting('site_phone') }}
                </a>
            @endif

            <span class="mx-1 flex items-center gap-1 text-xs">
                @foreach (config('yacht.locales') as $code => $cfg)
                    <a href="{{ locale_url($code) }}" hreflang="{{ $code }}"
                       class="rounded px-1.5 py-1 uppercase tracking-wider transition {{ app()->getLocale() === $code ? 'bg-brass-100 font-semibold text-brass-700' : 'opacity-70 hover:opacity-100' }}">
                        {{ $code }}
                    </a>
                @endforeach
            </span>
        </nav>

        <button type="button" @click="open = !open"
                class="ml-auto rounded-lg p-2 text-xl lg:hidden" aria-label="Menü">
            <i class="bi" :class="open ? 'bi-x' : 'bi-list'"></i>
        </button>
    </div>

    {{-- Mobil menü --}}
    <div x-show="open" x-transition x-cloak class="border-t border-sea-200 bg-white text-sea-900 lg:hidden">
        <div class="space-y-1 px-4 py-3">
            <a href="{{ lroute('tours.index') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sea-50">{{ __('site.nav.yachts') }}</a>
            <a href="{{ lroute('reservation.lookup') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sea-50">{{ __('site.nav.lookup') }}</a>
            <a href="{{ lroute('contact') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sea-50">{{ __('site.nav.contact') }}</a>
            @if (setting('site_phone'))
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', setting('site_phone')) }}"
                   class="block rounded-lg px-3 py-2 text-sm font-semibold text-brass-600 hover:bg-sea-50">
                    <i class="bi bi-telephone-fill mr-1"></i>{{ setting('site_phone') }}
                </a>
            @endif
            <div class="flex items-center gap-2 px-3 py-2">
                @foreach (config('yacht.locales') as $code => $cfg)
                    <a href="{{ locale_url($code) }}"
                       class="rounded px-2 py-1 text-xs uppercase {{ app()->getLocale() === $code ? 'bg-brass-100 font-semibold text-brass-700' : 'bg-sea-100 text-sea-600' }}">{{ $code }}</a>
                @endforeach
            </div>
        </div>
    </div>
</header>

<main id="content" class="{{ $transparentHeader ? '' : 'pt-16' }}">
    @if (session('status'))
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6">
            <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <i class="bi bi-check-circle"></i>{{ session('status') }}
            </div>
        </div>
    @endif

    @if ($errors->any() && ! request()->routeIs('*tours.show'))
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6">
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @yield('content')
</main>

<footer class="mt-24 bg-sea-950 text-sea-300">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <div class="mb-3 flex items-center gap-2 font-serif text-lg font-bold text-white">
                    <i class="bi bi-life-preserver text-brass-500"></i>
                    {{ setting('site_name', config('app.name')) }}
                </div>
                <p class="text-sm leading-relaxed">{{ __('site.footer.tagline') }}</p>
                <div class="mt-4 space-y-1 text-sm">
                    @if (setting('site_phone'))
                        <p><i class="bi bi-telephone mr-2 text-brass-500"></i>{{ setting('site_phone') }}</p>
                    @endif
                    @if (setting('site_email'))
                        <p><i class="bi bi-envelope mr-2 text-brass-500"></i>{{ setting('site_email') }}</p>
                    @endif
                </div>
            </div>

            <div>
                <h2 class="mb-3 font-sans text-[11px] font-semibold uppercase tracking-[0.12em] text-white">
                    {{ __('site.footer.company') }}
                </h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($footerPages as $page)
                        <li>
                            <a href="{{ lroute('pages.show', $page->slug) }}" class="transition hover:text-white">
                                {{ $page->getTranslation('title', app()->getLocale()) }}
                            </a>
                        </li>
                    @endforeach
                    <li><a href="{{ lroute('reservation.lookup') }}" class="transition hover:text-white">{{ __('site.nav.lookup') }}</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-12 flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-6 text-xs">
            <span>&copy; {{ date('Y') }} {{ setting('site_name', config('app.name')) }}</span>
            <span class="text-sea-400">{{ __('site.footer.no_payment') }}</span>
        </div>
    </div>
</footer>

@include('partials.cookie-consent')
@stack('scripts')
</body>
</html>

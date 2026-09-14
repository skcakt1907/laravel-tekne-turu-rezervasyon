@extends('layouts.site')

@php
    $siteName = setting('site_name', config('app.name'));
    $tagline = __('site.list.title');
    // Hero'daki fotoğraf yelpazesi: gerçek kapak görselleri varsa onları kullanır,
    // yoksa doku dolgusuna düşer. İçerik geldiğinde tasarım kendiliğinden zenginleşir.
    $heroShots = $featured->take(3)->map(fn ($y) => $y->coverUrl());
@endphp

@section('title', str_contains(mb_strtolower($siteName), mb_strtolower($tagline)) ? $siteName : $siteName.' — '.$tagline)
@section('meta_description', __('site.home.hero_sub'))
@section('header_style', 'transparent')

@section('content')

{{-- ---------------- HERO ---------------- --}}
<section class="bg-hero relative overflow-hidden pt-28 pb-20 sm:pt-32 lg:pt-40 lg:pb-28">
    {{-- ince ızgara dokusu --}}
    <div class="pointer-events-none absolute inset-0 opacity-[0.07]"
         style="background-image:linear-gradient(rgba(255,255,255,.6) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.6) 1px,transparent 1px);background-size:72px 72px"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6">
        <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">

            <div class="text-white">
                {{-- "Ödeme yok" sayfanın en görünür ikinci öğesi: müşteri
                     sitede kart bilgisi istenmeyeceğini başlıktan önce görmeli. --}}
                <p class="mb-4 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <span class="font-serif text-3xl font-bold uppercase tracking-wide text-brass-300 sm:text-4xl">
                        {{ __('site.home.eyebrow') }}
                    </span>
                    {{-- Devami da sari: iki parca tek bir cumle gibi okunsun,
                         "odeme yok" ile "teknede odenir" birbirinden kopmasin. --}}
                    <span class="text-lg font-semibold text-brass-200 sm:text-xl">
                        {{ __('site.home.eyebrow_sub') }}
                    </span>
                </p>

                <h1 class="text-4xl font-bold leading-[1.05] sm:text-5xl lg:text-6xl">
                    {{ __('site.home.hero_title') }}
                </h1>

                <p class="mt-5 max-w-xl text-lg leading-relaxed text-sea-200">
                    {{ __('site.home.hero_sub') }}
                </p>

            </div>

            {{-- fotoğraf yelpazesi --}}
            <div class="relative hidden lg:block" aria-hidden="true">
                <div class="relative mx-auto h-[420px] w-full max-w-lg">
                    @foreach ([
                        ['rotate' => '-8deg', 'top' => '40px', 'left' => '0', 'z' => 10, 'w' => '58%'],
                        ['rotate' => '4deg', 'top' => '0', 'left' => '30%', 'z' => 20, 'w' => '62%'],
                        ['rotate' => '10deg', 'top' => '190px', 'left' => '16%', 'z' => 30, 'w' => '54%'],
                    ] as $i => $shot)
                        <div class="absolute overflow-hidden rounded-2xl border border-white/20 shadow-2xl shadow-sea-950/50"
                             style="transform:rotate({{ $shot['rotate'] }});top:{{ $shot['top'] }};left:{{ $shot['left'] }};z-index:{{ $shot['z'] }};width:{{ $shot['w'] }}">
                            <div class="aspect-4/3 {{ $heroShots[$i] ?? null ? '' : 'bg-sea-800' }}">
                                @if ($heroShots[$i] ?? null)
                                    <img src="{{ $heroShots[$i] }}" alt="" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center text-sea-600">
                                        <i class="bi bi-water text-4xl"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ---------------- TURLARIMIZ ---------------- --}}
<section class="mx-auto max-w-7xl px-4 py-20 sm:px-6">
    <h2 class="mb-8 text-center text-3xl font-bold sm:text-4xl">{{ __('site.home.featured_title') }}</h2>

    @if ($featured->isEmpty())
        <div class="panel py-16 text-center text-sea-500">
            <i class="bi bi-water mb-3 block text-4xl text-sea-300"></i>
            {{ __('site.list.no_results') }}
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($featured as $yacht)
                @include('partials.yacht-card', ['yacht' => $yacht])
            @endforeach
        </div>
    @endif
</section>

{{-- ---------------- NASIL ÇALIŞIR ---------------- --}}
<section class="border-y border-sea-200 bg-white py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <p class="eyebrow mb-2">{{ __('site.home.how') }}</p>
            <h2 class="text-3xl font-bold sm:text-4xl">{{ __('site.home.how_title') }}</h2>
            <p class="mt-3 text-sea-600">{{ __('site.home.how_sub') }}</p>
        </div>

        <ol class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (__('site.steps') as $i => $step)
                <li class="relative text-center">
                    <div class="mx-auto mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-brass-100 font-serif text-lg font-bold text-brass-700">
                        {{ $i + 1 }}
                    </div>
                    {{-- Baglanti cizgisi: daire ortalandigi icin bir sonraki
                         dairenin merkezine kadar uzaniyor. 1.375rem = daire
                         yaricapi, 1.5rem = gap-6. --}}
                    @if (! $loop->last)
                        <span class="pointer-events-none absolute left-[calc(50%+1.375rem)] top-5 hidden h-px w-[calc(100%-1.25rem)] bg-sea-200 lg:block"></span>
                    @endif
                    <h3 class="mb-1.5 font-sans text-base font-semibold">{{ $step[0] }}</h3>
                    <p class="text-sm leading-relaxed text-sea-600">{{ $step[1] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ---------------- YAT TİPLERİ ---------------- --}}
@if ($types->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6">
        <h2 class="mb-5 text-2xl font-bold">{{ __('site.home.types') }}</h2>
        <div class="flex flex-wrap gap-2.5">
            @foreach ($types as $type)
                <a href="{{ lroute('tours.index', ['type' => $type['key']]) }}"
                   class="btn btn-ghost btn-sm">
                    {{ $type['label'] }}
                    <span class="badge badge-soft">{{ $type['count'] }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif

{{-- ---------------- SSS ---------------- --}}
@if ($faqs->isNotEmpty())
    <section class="border-t border-sea-200 bg-white py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <div class="mb-8 text-center">
                <p class="eyebrow mb-2">{{ __('site.home.faq') }}</p>
                <h2 class="text-3xl font-bold">{{ __('site.home.faq_title') }}</h2>
            </div>

            <div class="divide-y divide-sea-200 border-y border-sea-200">
                @foreach ($faqs as $faq)
                    <details class="group py-4" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">
                            {{ $faq->getTranslation('question', app()->getLocale()) }}
                            <i class="bi bi-plus-lg shrink-0 text-brass-600 transition group-open:rotate-45"></i>
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-sea-600">
                            {{ $faq->getTranslation('answer', app()->getLocale()) }}
                        </p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
@endif

@endsection

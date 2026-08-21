@extends('layouts.site')

@section('title', __('site.owner_landing.title').' — '.setting('site_name', config('app.name')))
@section('meta_description', __('site.owner_landing.sub'))
@section('header_style', 'transparent')

@section('content')
<section class="bg-hero relative overflow-hidden pt-32 pb-24 text-white lg:pt-40">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
        <p class="eyebrow mb-4 text-brass-300">{{ __('site.nav.list_your_yacht') }}</p>
        <h1 class="text-4xl font-bold leading-tight sm:text-5xl">{{ __('site.owner_landing.title') }}</h1>
        <p class="mx-auto mt-5 max-w-2xl text-lg text-sea-200">{{ __('site.owner_landing.sub') }}</p>
        <a href="/yat-sahibi/register" class="btn btn-brass mt-8">
            {{ __('site.owner_landing.cta') }}<i class="bi bi-arrow-right"></i>
        </a>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-20 sm:px-6">
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach (__('site.owner_benefits') as $i => $benefit)
            <div class="panel">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-brass-100 text-brass-700">
                    <i class="bi {{ ['bi-calendar-check','bi-whatsapp','bi-tags','bi-graph-up'][$i] ?? 'bi-check2' }}"></i>
                </div>
                <h2 class="mb-1.5 font-sans text-base font-semibold">{{ $benefit[0] }}</h2>
                <p class="text-sm leading-relaxed text-sea-600">{{ $benefit[1] }}</p>
            </div>
        @endforeach
    </div>
</section>

@if ($faqs->isNotEmpty())
    <section class="border-t border-sea-200 bg-white py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="mb-8 text-center text-3xl font-bold">{{ __('site.home.faq') }}</h2>
            <div class="divide-y divide-sea-200 border-y border-sea-200">
                @foreach ($faqs as $faq)
                    <details class="group py-4" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">
                            {{ $faq->getTranslation('question', app()->getLocale()) }}
                            <i class="bi bi-plus-lg shrink-0 text-brass-600 transition group-open:rotate-45"></i>
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-sea-600">{{ $faq->getTranslation('answer', app()->getLocale()) }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection

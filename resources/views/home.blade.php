@extends('layouts.site')

@section('title', setting('site_name', config('app.name')).' — '.__('site.list.title'))
@section('meta_description', __('site.home.hero_sub'))

@section('content')

<section class="hero">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1 class="mb-3">{{ __('site.home.hero_title') }}</h1>
                <p class="lead mb-0">{{ __('site.home.hero_sub') }}</p>
            </div>
        </div>
    </div>
</section>

<div class="container" style="margin-top:-38px; position:relative; z-index:2;">
    @include('partials.search-form')
</div>

{{-- Öne çıkan yatlar --}}
<section class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h2 class="section-title mb-1">{{ __('site.home.featured') }}</h2>
            <p class="section-sub mb-0">{{ __('site.home.featured_sub') }}</p>
        </div>
        <a href="{{ lroute('yachts.index') }}" class="btn btn-outline-sea btn-sm">
            {{ __('site.home.all_yachts') }} <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    @if ($featured->isEmpty())
        <div class="empty-state">
            <i class="bi bi-water d-block mb-2"></i>
            <p class="mb-0">{{ __('site.list.no_results') }}</p>
        </div>
    @else
        <div class="row g-3 g-lg-4">
            @foreach ($featured as $yacht)
                <div class="col-12 col-sm-6 col-lg-4">
                    @include('partials.yacht-card', ['yacht' => $yacht])
                </div>
            @endforeach
        </div>
    @endif
</section>

{{-- Nasıl çalışır --}}
<section class="py-5" style="background:var(--surface);border-block:1px solid var(--line);">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title mb-1">{{ __('site.home.how') }}</h2>
            <p class="section-sub mx-auto mb-0">{{ __('site.home.how_sub') }}</p>
        </div>

        <div class="row g-3">
            @foreach (__('site.steps') as $i => $step)
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="step-card">
                        <div class="n mb-2">0{{ $i + 1 }}</div>
                        <h3 class="h6 fw-bold mb-1">{{ $step[0] }}</h3>
                        <p class="small text-muted-2 mb-0">{{ $step[1] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Popüler limanlar --}}
@if ($ports->isNotEmpty())
    <section class="container py-5">
        <h2 class="section-title mb-4">{{ __('site.home.ports') }}</h2>
        <div class="row g-3">
            @foreach ($ports as $port)
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="{{ lroute('locations.show', $port->slug) }}" class="port-card">
                        @if ($port->cover)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($port->cover) }}"
                                 alt="{{ $port->getTranslation('name', app()->getLocale()) }}" loading="lazy">
                        @endif
                        <span>
                            {{ $port->getTranslation('name', app()->getLocale()) }}
                            <small class="ms-2 opacity-75">{{ $port->yachts_count }}</small>
                        </span>
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endif

{{-- Yat tipleri --}}
@if ($types->isNotEmpty())
    <section class="container pb-5">
        <h2 class="section-title mb-3">{{ __('site.home.types') }}</h2>
        <div class="d-flex flex-wrap gap-2">
            @foreach ($types as $type)
                <a href="{{ lroute('yachts.index', ['type' => $type['key']]) }}"
                   class="btn btn-outline-sea btn-sm">
                    {{ $type['label'] }}
                    <span class="badge badge-soft ms-1">{{ $type['count'] }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif

{{-- SSS --}}
@if ($faqs->isNotEmpty())
    <section class="container pb-5">
        <h2 class="section-title mb-3">{{ __('site.home.faq') }}</h2>
        <div class="accordion" id="faq">
            @foreach ($faqs as $faq)
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq-{{ $faq->id }}">
                            {{ $faq->getTranslation('question', app()->getLocale()) }}
                        </button>
                    </h3>
                    <div id="faq-{{ $faq->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                         data-bs-parent="#faq">
                        <div class="accordion-body text-muted-2">
                            {{ $faq->getTranslation('answer', app()->getLocale()) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

@endsection

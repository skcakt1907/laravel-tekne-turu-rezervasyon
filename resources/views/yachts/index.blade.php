@extends('layouts.site')

@php
    $locale = app()->getLocale();
    $heading = $location
        ? $location->getTranslation('name', $locale).' — '.__('site.list.title')
        : __('site.list.title');
    $formAction = $location ? lroute('locations.show', $location->slug) : lroute('yachts.index');
@endphp

@section('title', $location ? ($location->getTranslation('seo_title', $locale) ?: $heading) : $heading)
@section('meta_description', $location ? strip_tags((string) $location->getTranslation('description', $locale)) : __('site.home.hero_sub'))

@section('content')

<div class="bg-sea text-white py-4 py-lg-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="small mb-2">
            <a href="{{ lroute('home') }}" class="text-white-50 text-decoration-none">{{ __('site.nav.yachts') }}</a>
            @if ($location)
                <span class="text-white-50 mx-1">/</span>
                <span class="text-white-50">{{ $location->getTranslation('name', $locale) }}</span>
            @endif
        </nav>
        <h1 class="h2 mb-1">{{ $heading }}</h1>
        <p class="mb-0 text-white-50">{{ __('site.list.results', ['count' => $yachts->total()]) }}</p>
    </div>
</div>

<div class="container py-4">
    @include('partials.search-form', ['action' => $formAction])

    @if ($location && $location->getTranslation('description', $locale))
        <div class="panel mt-4">
            {!! $location->getTranslation('description', $locale) !!}
        </div>
    @endif

    <div class="row g-4 mt-1">
        {{-- Filtreler --}}
        <aside class="col-12 col-lg-3">
            <form method="GET" action="{{ $formAction }}" id="filters">
                {{-- arama kutusundan gelen degerleri koru --}}
                @foreach (['port', 'start', 'end', 'guests'] as $keep)
                    @if (request()->filled($keep))
                        <input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">
                    @endif
                @endforeach

                <div class="filter-panel">
                    <h6>{{ __('site.list.sort') }}</h6>
                    <select name="sort" class="form-select form-select-sm" onchange="document.getElementById('filters').submit()">
                        @foreach (\App\Services\YachtSearch::SORTS as $sort)
                            <option value="{{ $sort }}" @selected(request('sort') === $sort)>
                                {{ __('site.sort.'.$sort) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-panel">
                    <h6>{{ __('site.list.type') }}</h6>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">—</option>
                        @foreach (yacht_type_options() as $key => $label)
                            <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <h6 class="mt-3">{{ __('site.list.cabins') }}</h6>
                    <select name="cabins" class="form-select form-select-sm">
                        <option value="">—</option>
                        @for ($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}" @selected(request('cabins') == $i)>{{ $i }}+</option>
                        @endfor
                    </select>

                    <h6 class="mt-3">{{ __('site.list.crew') }}</h6>
                    <div class="d-flex gap-3 small">
                        <label class="d-flex align-items-center gap-1">
                            <input type="radio" name="crew" value="" class="form-check-input mt-0" @checked(! request()->filled('crew'))>
                            —
                        </label>
                        <label class="d-flex align-items-center gap-1">
                            <input type="radio" name="crew" value="with" class="form-check-input mt-0" @checked(request('crew') === 'with')>
                            {{ __('site.list.with_crew') }}
                        </label>
                        <label class="d-flex align-items-center gap-1">
                            <input type="radio" name="crew" value="without" class="form-check-input mt-0" @checked(request('crew') === 'without')>
                            {{ __('site.list.without_crew') }}
                        </label>
                    </div>

                    <h6 class="mt-3">{{ __('site.list.price_range') }}</h6>
                    <div class="d-flex gap-2">
                        <input type="number" name="price_min" class="form-control form-control-sm"
                               placeholder="min" min="0" value="{{ request('price_min') }}">
                        <input type="number" name="price_max" class="form-control form-control-sm"
                               placeholder="max" min="0" value="{{ request('price_max') }}">
                    </div>
                </div>

                @if ($features->isNotEmpty())
                    <div class="filter-panel">
                        <h6>{{ __('site.list.features') }}</h6>
                        @foreach ($features as $group => $items)
                            <div class="mb-2">
                                @foreach ($items as $feature)
                                    <label class="d-flex align-items-center gap-2 small mb-1">
                                        <input type="checkbox" name="features[]" value="{{ $feature->id }}"
                                               class="form-check-input mt-0"
                                               @checked(in_array((string) $feature->id, (array) request('features', []), true))>
                                        {{ $feature->getTranslation('name', $locale) }}
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="btn btn-brass btn-sm">{{ __('site.list.apply') }}</button>
                    <a href="{{ $formAction }}" class="btn btn-outline-sea btn-sm">{{ __('site.list.clear') }}</a>
                </div>
            </form>
        </aside>

        {{-- Sonuçlar --}}
        <div class="col-12 col-lg-9">
            @if ($yachts->isEmpty())
                <div class="empty-state panel">
                    <i class="bi bi-binoculars d-block mb-2"></i>
                    <p class="mb-1 fw-semibold">{{ __('site.list.no_results') }}</p>
                    <p class="small mb-0">{{ __('site.list.no_results_hint') }}</p>
                </div>
            @else
                <div class="row g-3 g-lg-4">
                    @foreach ($yachts as $yacht)
                        <div class="col-12 col-sm-6 col-xl-4">
                            @include('partials.yacht-card', ['yacht' => $yacht])
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $yachts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

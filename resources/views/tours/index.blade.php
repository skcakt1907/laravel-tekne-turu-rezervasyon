@extends('layouts.site')

@php
    $locale = app()->getLocale();
    $heading = __('site.list.title');
    $formAction = lroute('tours.index');
    $activeFilters = collect(request()->except(['page', 'sort']))->filter()->count();
@endphp

@section('title', $heading)
@section('meta_description', __('site.home.hero_sub'))

@section('content')

<div class="bg-sea-900 pb-20 pt-10 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <nav class="mb-3 text-sm text-sea-300">
            <a href="{{ lroute('home') }}" class="transition hover:text-white">{{ setting('site_name', config('app.name')) }}</a>
            <span class="mx-1.5 text-sea-500">/</span>
            <span class="text-white">{{ __('site.nav.yachts') }}</span>
        </nav>

        <h1 class="text-3xl font-bold sm:text-4xl">{{ $heading }}</h1>
        <p class="mt-2 text-sea-300">{{ __('site.list.results', ['count' => $yachts->total()]) }}</p>
    </div>
</div>

<div class="mx-auto -mt-12 max-w-7xl px-4 sm:px-6">
    @include('partials.search-form', ['action' => $formAction])

    <div class="mt-8 grid gap-8 lg:grid-cols-[280px_1fr]">

        {{-- ---------------- FİLTRELER ---------------- --}}
        <aside x-data="{ open: false }" class="lg:sticky lg:top-24 lg:self-start">
            <button type="button" @click="open = !open"
                    class="btn btn-ghost w-full justify-between lg:hidden">
                <span><i class="bi bi-sliders"></i> {{ __('site.list.filters') }}</span>
                @if ($activeFilters)
                    <span class="badge badge-brass">{{ $activeFilters }}</span>
                @endif
            </button>

            <form method="GET" action="{{ $formAction }}" id="filters"
                  x-show="open || window.innerWidth >= 1024"
                  x-cloak
                  class="mt-3 space-y-4 lg:mt-0 lg:block">

                @foreach (['start', 'end', 'guests'] as $keep)
                    @if (request()->filled($keep))
                        <input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">
                    @endif
                @endforeach

                <div class="panel space-y-4">
                    <div>
                        <label class="label" for="f-sort">{{ __('site.list.sort') }}</label>
                        <select name="sort" id="f-sort" class="field"
                                onchange="document.getElementById('filters').submit()">
                            @foreach (\App\Services\YachtSearch::SORTS as $sort)
                                <option value="{{ $sort }}" @selected(request('sort') === $sort)>{{ __('site.sort.'.$sort) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label" for="f-type">{{ __('site.list.type') }}</label>
                        <select name="type" id="f-type" class="field">
                            <option value="">—</option>
                            @foreach (yacht_type_options() as $key => $label)
                                <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label" for="f-cabins">{{ __('site.list.cabins') }}</label>
                        <select name="cabins" id="f-cabins" class="field">
                            <option value="">—</option>
                            @for ($i = 1; $i <= 8; $i++)
                                <option value="{{ $i }}" @selected(request('cabins') == $i)>{{ $i }}+</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <span class="label">{{ __('site.list.crew') }}</span>
                        <div class="grid grid-cols-3 gap-1.5">
                            @foreach ([['', '—'], ['with', __('site.list.with_crew')], ['without', __('site.list.without_crew')]] as [$value, $label])
                                <label class="cursor-pointer">
                                    <input type="radio" name="crew" value="{{ $value }}" class="peer sr-only"
                                           @checked(request('crew', '') === $value)>
                                    <span class="block rounded-lg border border-sea-200 px-2 py-2 text-center text-xs transition peer-checked:border-brass-400 peer-checked:bg-brass-50 peer-checked:font-semibold peer-checked:text-brass-700">
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <span class="label">{{ __('site.list.price_range') }}</span>
                        <div class="flex items-center gap-2">
                            <input type="number" name="price_min" class="field" placeholder="min" min="0" value="{{ request('price_min') }}">
                            <span class="text-sea-400">–</span>
                            <input type="number" name="price_max" class="field" placeholder="max" min="0" value="{{ request('price_max') }}">
                        </div>
                    </div>
                </div>

                @if ($features->isNotEmpty())
                    <div class="panel">
                        <span class="label">{{ __('site.list.features') }}</span>
                        <div class="max-h-72 space-y-1.5 overflow-y-auto pr-1">
                            @foreach ($features as $group => $items)
                                @foreach ($items as $feature)
                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-sea-700">
                                        <input type="checkbox" name="features[]" value="{{ $feature->id }}"
                                               class="h-4 w-4 rounded border-sea-300 text-brass-600 focus:ring-brass-300"
                                               @checked(in_array((string) $feature->id, (array) request('features', []), true))>
                                        {{ $feature->getTranslation('name', $locale) }}
                                    </label>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-brass flex-1">{{ __('site.list.apply') }}</button>
                    <a href="{{ $formAction }}" class="btn btn-ghost">{{ __('site.list.clear') }}</a>
                </div>
            </form>
        </aside>

        {{-- ---------------- SONUÇLAR ---------------- --}}
        <div>
            @if ($yachts->isEmpty())
                <div class="panel py-20 text-center">
                    <i class="bi bi-binoculars mb-3 block text-4xl text-sea-300"></i>
                    <p class="font-semibold">{{ __('site.list.no_results') }}</p>
                    <p class="mt-1 text-sm text-sea-500">{{ __('site.list.no_results_hint') }}</p>
                    <a href="{{ $formAction }}" class="btn btn-ghost btn-sm mt-5">{{ __('site.list.clear') }}</a>
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($yachts as $yacht)
                        @include('partials.yacht-card', ['yacht' => $yacht])
                    @endforeach
                </div>

                <div class="mt-10">{{ $yachts->links() }}</div>
            @endif
        </div>
    </div>
</div>

@endsection

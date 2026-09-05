@extends('layouts.site')

@php
    $locale = app()->getLocale();
    $name = $yacht->getTranslation('name', $locale);
    $cover = $yacht->coverUrl();

    $specs = array_filter([
        'length' => $yacht->length_m ? rtrim(rtrim(number_format((float) $yacht->length_m, 1, ',', '.'), '0'), ',').' m' : null,
        'cabins' => $yacht->cabins,
        'beds' => $yacht->beds,
        'wc' => $yacht->wc,
        'capacity' => $yacht->capacity,
        'sleep_capacity' => $yacht->sleep_capacity,
        'year' => $yacht->build_year,
        'brand' => $yacht->brand,
        'model' => $yacht->model,
        'engine' => $yacht->engine,
    ]);
@endphp

@section('title', $name.' — '.setting('site_name', config('app.name')))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $yacht->getTranslation('description', $locale)), 155))
@section('og_type', 'product')
@section('og_image', $cover ?? asset('images/yacht-placeholder.svg'))

@push('head')
    @include('partials.schema-yacht', ['yacht' => $yacht])
@endpush

@section('content')

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">

    <nav class="mb-4 text-sm text-sea-500">
        <a href="{{ lroute('tours.index') }}" class="transition hover:text-brass-700">{{ __('site.nav.yachts') }}</a>
        <span class="mx-1.5 text-sea-300">/</span>
        <span class="text-sea-900">{{ $name }}</span>
    </nav>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold sm:text-4xl">{{ $name }}</h1>
            <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-sea-600">
                <span>{{ yacht_type_label($yacht->type) }}</span>
                <span class="text-sea-300">·</span>
                <span>{{ $yacht->with_crew ? __('site.list.with_crew') : __('site.list.without_crew') }}</span>
                @unless ($yacht->is_open)
                    <span class="badge badge-warn ml-1">{{ __('site.detail.closed') }}</span>
                @endunless
            </p>
        </div>

        @if ($yacht->price_from)
            <div class="text-right">
                <div class="font-serif text-3xl font-bold">{{ money($yacht->price_from, $yacht->currency) }}</div>
                <div class="text-xs uppercase tracking-wide text-sea-500">
                    / {{ __('site.card.per_person') }}
                </div>
            </div>
        @endif
    </div>

    {{-- ---------------- GALERİ ---------------- --}}
    <div x-data="{ current: '{{ $cover ?? '' }}' }" class="mb-10">
        <div class="aspect-16/9 overflow-hidden rounded-2xl {{ $cover ? 'bg-sea-100' : 'bg-placeholder' }}">
            @if ($cover)
                <img :src="current" src="{{ $cover }}" alt="{{ $name }}" class="h-full w-full object-cover">
            @else
                <div class="flex h-full flex-col items-center justify-center gap-2 text-sea-400">
                    <i class="bi bi-image text-5xl"></i>
                    <span class="text-sm">{{ __('site.detail.no_photo') }}</span>
                </div>
            @endif
        </div>

        @if ($yacht->photos->count() > 1)
            <div class="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-8">
                @foreach ($yacht->photos as $photo)
                    <button type="button" @click="current = '{{ $photo->url() }}'"
                            class="aspect-4/3 overflow-hidden rounded-lg border-2 transition"
                            :class="current === '{{ $photo->url() }}' ? 'border-brass-500' : 'border-transparent hover:border-sea-300'">
                        <img src="{{ $photo->url() }}" alt="{{ $photo->getTranslation('alt', $locale) ?: $name }}"
                             loading="lazy" class="h-full w-full object-cover">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
        <div class="space-y-6">

            {{-- Teknik bilgiler --}}
            <section class="panel">
                <h2 class="mb-4 text-xl font-bold">{{ __('site.detail.specs') }}</h2>
                <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($specs as $key => $value)
                        <div class="rounded-lg bg-sea-50 px-3 py-2.5">
                            <dt class="text-[10px] uppercase tracking-wider text-sea-500">{{ __('site.detail.'.$key) }}</dt>
                            <dd class="mt-0.5 font-semibold">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            @if ($yacht->getTranslation('description', $locale))
                <section class="panel">
                    <h2 class="mb-3 text-xl font-bold">{{ __('site.detail.about') }}</h2>
                    <div class="prose-site">{!! $yacht->getTranslation('description', $locale) !!}</div>
                </section>
            @endif

            @if ($yacht->features->isNotEmpty())
                <section class="panel">
                    <h2 class="mb-4 text-xl font-bold">{{ __('site.detail.features') }}</h2>
                    <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($yacht->features as $feature)
                            <li class="flex items-center gap-2 text-sm text-sea-700">
                                <i class="bi bi-check2 text-emerald-600"></i>
                                {{ $feature->getTranslation('name', $locale) }}
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Fiyat tablosu --}}
            @if ($rates->isNotEmpty())
                <section class="panel">
                    <h2 class="mb-4 text-xl font-bold">{{ __('site.detail.prices') }}</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-sea-200 text-left text-[11px] uppercase tracking-wider text-sea-500">
                                    <th class="pb-2 pr-4 font-semibold">{{ __('site.detail.period') }}</th>
                                    <th class="pb-2 pr-4 text-right font-semibold">{{ __('site.booking.adults') }}</th>
                                    <th class="pb-2 text-right font-semibold">{{ __('site.booking.children') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-sea-100">
                                @foreach ($rates as $rate)
                                    <tr>
                                        <td class="py-2.5 pr-4 text-sea-600">
                                            @if ($rate->isBase())
                                                {{ __('site.detail.base_price') }}
                                            @else
                                                {{ $rate->season_start->format('d.m.Y') }} – {{ $rate->season_end->format('d.m.Y') }}
                                                @if ($rate->label)
                                                    <span class="badge badge-soft ml-1">{{ $rate->label }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="py-2.5 pr-4 text-right font-semibold">{{ money($rate->price, $yacht->currency) }}</td>
                                        <td class="py-2.5 text-right font-semibold">{{ money($rate->price_child ?? $rate->price, $yacht->currency) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4 rounded-lg border-l-4 border-brass-500 bg-brass-50 px-4 py-2.5 text-xs text-brass-800">
                        {{ __('site.booking.estimate_note') }}
                    </p>
                </section>
            @endif

            {{-- Ek ücretler --}}
            @if ($yacht->extras->isNotEmpty())
                <section class="panel">
                    <h2 class="mb-4 text-xl font-bold">{{ __('site.detail.extras') }}</h2>
                    <ul class="divide-y divide-sea-100">
                        @foreach ($yacht->extras as $extra)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm">
                                <span class="flex items-center gap-2">
                                    {{ $extra->getTranslation('name', $locale) }}
                                    <span class="{{ $extra->is_required ? 'badge badge-brass' : 'badge badge-soft' }}">
                                        {{ $extra->is_required ? __('site.detail.required') : __('site.detail.optional') }}
                                    </span>
                                </span>
                                <span class="font-semibold">
                                    {{ money($extra->amount, $yacht->currency) }}
                                    <span class="text-xs font-normal text-sea-500">/ {{ __('site.calc.'.$extra->calculation->value) }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Müsaitlik takvimi --}}
            <section class="panel">
                <h2 class="mb-4 text-xl font-bold">{{ __('site.detail.calendar') }}</h2>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @for ($m = 0; $m < 3; $m++)
                        @php
                            $month = now()->startOfMonth()->addMonths($m);
                            $first = $month->copy()->startOfMonth();
                            $offset = $first->dayOfWeekIso - 1;
                        @endphp
                        <div>
                            <div class="mb-2 text-sm font-semibold">{{ $month->translatedFormat('F Y') }}</div>
                            <div class="grid grid-cols-7 gap-1 text-center">
                                @for ($dw = 1; $dw <= 7; $dw++)
                                    <div class="pb-1 text-[10px] uppercase text-sea-400">
                                        {{ \Illuminate\Support\Carbon::now()->startOfWeek()->addDays($dw - 1)->isoFormat('dd') }}
                                    </div>
                                @endfor
                                @for ($i = 0; $i < $offset; $i++)
                                    <div></div>
                                @endfor
                                @for ($d = 1; $d <= $month->daysInMonth; $d++)
                                    @php
                                        $date = $first->copy()->addDays($d - 1);
                                        $isPast = $date->isBefore(now()->startOfDay());
                                        $remaining = $seats[$date->toDateString()] ?? null;
                                        $isFull = ! $isPast && $remaining !== null && $remaining <= 0;
                                    @endphp
                                    <div title="{{ $date->format('d.m.Y') }}{{ $remaining !== null ? ' — '.trans_choice('site.detail.seats_left', $remaining, ['count' => $remaining]) : '' }}"
                                         class="grid aspect-square place-items-center rounded text-xs
                                            {{ $isPast ? 'text-sea-300' : ($isFull ? 'bg-brass-100 text-brass-700 line-through' : 'bg-sea-50 text-sea-700') }}">
                                        {{ $d }}
                                    </div>
                                @endfor
                            </div>
                        </div>
                    @endfor
                </div>
                <div class="mt-4 flex gap-5 text-xs text-sea-600">
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-sea-50 ring-1 ring-sea-200"></span>{{ __('site.detail.free') }}</span>
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-brass-100"></span>{{ __('site.detail.busy') }}</span>
                </div>
            </section>

            @if ($yacht->getTranslation('rules', $locale))
                <section class="panel">
                    <h2 class="mb-3 text-xl font-bold">{{ __('site.detail.rules') }}</h2>
                    <div class="prose-site">{!! $yacht->getTranslation('rules', $locale) !!}</div>
                </section>
            @endif
        </div>

        {{-- ---------------- REZERVASYON ---------------- --}}
        <div>
            <div class="lg:sticky lg:top-24">
                <div class="panel">
                    <h2 class="mb-1 text-xl font-bold">{{ __('site.booking.title') }}</h2>
                    <p class="mb-4 text-xs text-sea-500">
                        <i class="bi bi-shield-check text-emerald-600"></i> {{ __('site.booking.no_payment') }}
                    </p>

                    @if (! $yacht->is_open)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            {{ __('site.detail.closed') }}
                        </div>
                    @else
                        @if ($errors->any())
                            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                                <ul class="list-disc space-y-1 pl-4">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ lroute('reservation.store') }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="yacht_id" value="{{ $yacht->id }}">

                            <div>
                                <label class="label" for="b-date">{{ __('site.booking.date') }}</label>
                                <input type="date" name="date" id="b-date" class="field"
                                       min="{{ now()->addDay()->toDateString() }}"
                                       value="{{ old('date', $prefill['date']) }}" required>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="label" for="b-adults">{{ __('site.booking.adults') }}</label>
                                    <input type="number" name="adults" id="b-adults" class="field" min="1"
                                           max="{{ $yacht->capacity ?: 100 }}"
                                           value="{{ old('adults', $prefill['adults']) }}" required>
                                </div>
                                <div>
                                    <label class="label" for="b-children">{{ __('site.booking.children') }}</label>
                                    <input type="number" name="children" id="b-children" class="field" min="0"
                                           max="{{ $yacht->capacity ?: 100 }}"
                                           value="{{ old('children', $prefill['children']) }}">
                                </div>
                            </div>

                            @if ($yacht->extras->where('is_required', false)->isNotEmpty())
                                <div>
                                    <span class="label">{{ __('site.booking.extras') }}</span>
                                    <div class="space-y-1.5">
                                        @foreach ($yacht->extras->where('is_required', false) as $extra)
                                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                <input type="checkbox" name="extras[]" value="{{ $extra->id }}"
                                                       class="h-4 w-4 rounded border-sea-300 text-brass-600 focus:ring-brass-300">
                                                <span class="flex-1">{{ $extra->getTranslation('name', $locale) }}</span>
                                                <span class="text-sea-500">{{ money($extra->amount, $yacht->currency) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <hr class="border-sea-100">

                            <input type="text" name="customer_name" class="field" required
                                   placeholder="{{ __('site.booking.name') }}" value="{{ old('customer_name') }}">
                            <input type="email" name="customer_email" class="field" required
                                   placeholder="{{ __('site.booking.email') }}" value="{{ old('customer_email') }}">
                            <input type="tel" name="customer_phone" class="field" required
                                   placeholder="{{ __('site.booking.phone') }}" value="{{ old('customer_phone') }}">
                            <input type="tel" name="customer_whatsapp" class="field"
                                   placeholder="{{ __('site.booking.whatsapp') }}" value="{{ old('customer_whatsapp') }}">
                            <textarea name="message" rows="2" class="field"
                                      placeholder="{{ __('site.booking.message') }}">{{ old('message') }}</textarea>

                            <label class="flex cursor-pointer gap-2 text-xs text-sea-600">
                                <input type="checkbox" name="kvkk" value="1" required
                                       class="mt-0.5 h-4 w-4 rounded border-sea-300 text-brass-600 focus:ring-brass-300">
                                <span>{{ __('site.booking.kvkk') }}</span>
                            </label>
                            <label class="flex cursor-pointer gap-2 text-xs text-sea-600">
                                <input type="checkbox" name="whatsapp_consent" value="1" required
                                       class="mt-0.5 h-4 w-4 rounded border-sea-300 text-brass-600 focus:ring-brass-300">
                                <span>{{ __('site.booking.whatsapp_consent') }}</span>
                            </label>

                            <p class="rounded-lg border-l-4 border-brass-500 bg-brass-50 px-3 py-2 text-xs text-brass-800">
                                {{ __('site.booking.estimate_note') }}
                            </p>

                            <button type="submit" class="btn btn-brass w-full">
                                {{ __('site.booking.submit') }}<i class="bi bi-arrow-right"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Benzer turlar --}}
    @if ($similar->isNotEmpty())
        <section class="mt-16">
            <h2 class="mb-6 text-2xl font-bold">{{ __('site.detail.similar') }}</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($similar as $other)
                    @include('partials.yacht-card', ['yacht' => $other])
                @endforeach
            </div>
        </section>
    @endif
</div>

@endsection

@extends('layouts.site')

@php
    use Illuminate\Support\Carbon;

    $locale = app()->getLocale();
    $name = $yacht->getTranslation('name', $locale);
    $cover = $yacht->coverUrl() ?? asset('images/yacht-placeholder.svg');

    // Kapalı günleri tek bir kümede topla (takvim boyaması için)
    $busyDays = [];
    foreach ($blocked as $range) {
        $cursor = Carbon::parse($range['start']);
        $stop = Carbon::parse($range['end']);
        while ($cursor->lessThan($stop)) {
            $busyDays[$cursor->toDateString()] = true;
            $cursor->addDay();
        }
    }

    $unitLabels = [
        'hour' => __('site.card.per_hour'),
        'day' => __('site.card.per_day'),
        'week' => __('site.card.per_week'),
    ];
@endphp

@section('title', $name.' — '.setting('site_name', config('app.name')))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $yacht->getTranslation('description', $locale)), 155))
@section('og_type', 'product')
@section('og_image', $cover)

@section('content')

<div class="container py-4">
    <nav class="small mb-3">
        <a href="{{ lroute('yachts.index') }}" class="text-decoration-none">{{ __('site.nav.yachts') }}</a>
        @if ($yacht->location)
            <span class="text-muted-2 mx-1">/</span>
            <a href="{{ lroute('locations.show', $yacht->location->slug) }}" class="text-decoration-none">
                {{ $yacht->location->getTranslation('name', $locale) }}
            </a>
        @endif
        <span class="text-muted-2 mx-1">/</span>
        <span class="text-muted-2">{{ $name }}</span>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <h1 class="h2 mb-1">{{ $name }}</h1>
            <div class="text-muted-2">
                <i class="bi bi-geo-alt me-1"></i>{{ $yacht->location?->getTranslation('name', $locale) ?? '—' }}
                <span class="mx-1">·</span>{{ yacht_type_label($yacht->type) }}
                <span class="mx-1">·</span>{{ $yacht->with_crew ? __('site.list.with_crew') : __('site.list.without_crew') }}
            </div>
        </div>
        @if ($yacht->price_from)
            <div class="text-lg-end">
                <div class="fs-4 fw-bold">{{ money($yacht->price_from, $yacht->currency) }}</div>
                <div class="small text-muted-2">/ {{ $unitLabels[$yacht->price_from_unit] ?? '' }}</div>
            </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            {{-- Galeri --}}
            <div class="gallery-main ratio ratio-16x9 mb-2">
                <img id="gallery-main-img" src="{{ $cover }}" alt="{{ $name }}"
                     style="object-fit:cover;width:100%;height:100%">
            </div>
            @if ($yacht->photos->count() > 1)
                <div class="row g-2 gallery-thumbs mb-4">
                    @foreach ($yacht->photos as $photo)
                        <div class="col-3 col-md-2">
                            <img src="{{ $photo->url() }}"
                                 alt="{{ $photo->getTranslation('alt', $locale) ?: $name }}"
                                 class="{{ $loop->first ? 'active' : '' }}"
                                 data-full="{{ $photo->url() }}" loading="lazy">
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Teknik bilgiler --}}
            <div class="panel">
                <h2>{{ __('site.detail.specs') }}</h2>
                <div class="spec-grid">
                    @foreach ([
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
                    ] as $key => $value)
                        @if ($value)
                            <div>
                                <div class="k">{{ __('site.detail.'.$key) }}</div>
                                <div class="v">{{ $value }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            @if ($yacht->getTranslation('description', $locale))
                <div class="panel">
                    <h2>{{ __('site.detail.about') }}</h2>
                    <div class="text-muted-2">{!! $yacht->getTranslation('description', $locale) !!}</div>
                </div>
            @endif

            @if ($yacht->features->isNotEmpty())
                <div class="panel">
                    <h2>{{ __('site.detail.features') }}</h2>
                    <div class="row g-2">
                        @foreach ($yacht->features as $feature)
                            <div class="col-6 col-md-4 small">
                                <i class="bi bi-check2 me-1" style="color:var(--ok)"></i>{{ $feature->getTranslation('name', $locale) }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Fiyat tablosu --}}
            @if ($rates->isNotEmpty())
                <div class="panel">
                    <h2>{{ __('site.detail.prices') }}</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-2">
                            <thead>
                                <tr class="small text-muted-2">
                                    <th>{{ __('site.detail.unit') }}</th>
                                    <th>{{ __('site.detail.period') }}</th>
                                    <th class="text-end">{{ __('site.detail.price') }}</th>
                                    <th class="text-end">{{ __('site.detail.min') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rates as $unit => $group)
                                    @foreach ($group as $rate)
                                        <tr>
                                            <td>{{ __('site.units.'.$rate->unit->value) }}</td>
                                            <td class="small text-muted-2">
                                                @if ($rate->isBase())
                                                    {{ __('site.detail.base_price') }}
                                                @else
                                                    {{ $rate->season_start->format('d.m.Y') }} – {{ $rate->season_end->format('d.m.Y') }}
                                                    @if ($rate->label) <span class="badge badge-soft ms-1">{{ $rate->label }}</span> @endif
                                                @endif
                                            </td>
                                            <td class="text-end fw-semibold">{{ money($rate->price, $yacht->currency) }}</td>
                                            <td class="text-end small text-muted-2">{{ $rate->min_duration }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="estimate-note">{{ __('site.booking.estimate_note') }}</div>
                </div>
            @endif

            {{-- Ek ücretler --}}
            @if ($yacht->extras->isNotEmpty())
                <div class="panel">
                    <h2>{{ __('site.detail.extras') }}</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <tbody>
                                @foreach ($yacht->extras as $extra)
                                    <tr>
                                        <td>{{ $extra->getTranslation('name', $locale) }}</td>
                                        <td>
                                            <span class="badge {{ $extra->is_required ? 'badge-brass' : 'badge-soft' }}">
                                                {{ $extra->is_required ? __('site.detail.required') : __('site.detail.optional') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            {{ money($extra->amount, $yacht->currency) }}
                                            <small class="text-muted-2">/ {{ __('site.calc.'.$extra->calculation->value) }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Müsaitlik takvimi --}}
            <div class="panel">
                <h2>{{ __('site.detail.calendar') }}</h2>
                <div class="row g-3">
                    @for ($m = 0; $m < 3; $m++)
                        @php
                            $month = now()->startOfMonth()->addMonths($m);
                            $first = $month->copy()->startOfMonth();
                            $daysInMonth = $month->daysInMonth;
                            $offset = ($first->dayOfWeekIso - 1); // pazartesi = 0
                        @endphp
                        <div class="col-12 col-md-4">
                            <div class="cal">
                                <div class="cal-head">
                                    <span class="cal-title">{{ $month->translatedFormat('F Y') }}</span>
                                </div>
                                <div class="cal-grid">
                                    @for ($dw = 1; $dw <= 7; $dw++)
                                        <div class="cal-dow">{{ \Illuminate\Support\Carbon::now()->startOfWeek()->addDays($dw - 1)->isoFormat('dd') }}</div>
                                    @endfor
                                    @for ($i = 0; $i < $offset; $i++)
                                        <div class="cal-day empty"></div>
                                    @endfor
                                    @for ($d = 1; $d <= $daysInMonth; $d++)
                                        @php
                                            $date = $first->copy()->addDays($d - 1);
                                            $key = $date->toDateString();
                                            $isPast = $date->isBefore(now()->startOfDay());
                                            $isBusy = isset($busyDays[$key]);
                                        @endphp
                                        <div class="cal-day {{ $isPast ? 'past' : ($isBusy ? 'busy' : '') }}"
                                             title="{{ $date->format('d.m.Y') }}{{ $isBusy ? ' — '.__('site.detail.busy') : '' }}">{{ $d }}</div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
                <div class="cal-legend">
                    <span><i style="background:var(--surface-2)"></i>{{ __('site.detail.free') }}</span>
                    <span><i style="background:var(--brass-wash)"></i>{{ __('site.detail.busy') }}</span>
                </div>
            </div>

            @if ($yacht->getTranslation('rules', $locale))
                <div class="panel">
                    <h2>{{ __('site.detail.rules') }}</h2>
                    <div class="text-muted-2">{!! $yacht->getTranslation('rules', $locale) !!}</div>
                </div>
            @endif
        </div>

        {{-- Rezervasyon formu --}}
        <div class="col-12 col-lg-4">
            <div class="booking-box">
                <div class="panel">
                    <h2>{{ __('site.booking.title') }}</h2>

                    @if (! $yacht->is_open)
                        <div class="alert alert-warning small mb-0">{{ __('site.detail.closed') }}</div>
                    @else
                        @if ($errors->any())
                            <div class="alert alert-danger small">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ lroute('reservation.store') }}" class="vstack gap-2">
                            @csrf
                            <input type="hidden" name="yacht_id" value="{{ $yacht->id }}">

                            <div>
                                <label class="form-label small mb-1">{{ __('site.booking.unit') }}</label>
                                <select name="unit" class="form-select form-select-sm" required>
                                    @foreach ($yacht->activeUnits() as $unit)
                                        <option value="{{ $unit }}" @selected(old('unit', $prefill['unit']) === $unit)>
                                            {{ __('site.units.'.$unit) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1">{{ __('site.booking.start') }}</label>
                                    <input type="datetime-local" name="starts_at" class="form-control form-control-sm"
                                           value="{{ old('starts_at', $prefill['start'] ? $prefill['start'].'T10:00' : '') }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">{{ __('site.booking.end') }}</label>
                                    <input type="datetime-local" name="ends_at" class="form-control form-control-sm"
                                           value="{{ old('ends_at', $prefill['end'] ? $prefill['end'].'T10:00' : '') }}" required>
                                </div>
                            </div>

                            <div>
                                <label class="form-label small mb-1">{{ __('site.booking.guests') }}</label>
                                <input type="number" name="guests" class="form-control form-control-sm"
                                       min="1" max="{{ $yacht->capacity ?: 100 }}"
                                       value="{{ old('guests', $prefill['guests']) }}" required>
                            </div>

                            @if ($yacht->extras->where('is_required', false)->isNotEmpty())
                                <div>
                                    <label class="form-label small mb-1">{{ __('site.booking.extras') }}</label>
                                    @foreach ($yacht->extras->where('is_required', false) as $extra)
                                        <label class="d-flex align-items-center gap-2 small mb-1">
                                            <input type="checkbox" name="extras[]" value="{{ $extra->id }}" class="form-check-input mt-0">
                                            {{ $extra->getTranslation('name', $locale) }}
                                            <span class="text-muted-2 ms-auto">{{ money($extra->amount, $yacht->currency) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif

                            <hr class="my-1">

                            <input type="text" name="customer_name" class="form-control form-control-sm"
                                   placeholder="{{ __('site.booking.name') }}" value="{{ old('customer_name') }}" required>
                            <input type="email" name="customer_email" class="form-control form-control-sm"
                                   placeholder="{{ __('site.booking.email') }}" value="{{ old('customer_email') }}" required>
                            <input type="tel" name="customer_phone" class="form-control form-control-sm"
                                   placeholder="{{ __('site.booking.phone') }}" value="{{ old('customer_phone') }}" required>
                            <input type="tel" name="customer_whatsapp" class="form-control form-control-sm"
                                   placeholder="{{ __('site.booking.whatsapp') }}" value="{{ old('customer_whatsapp') }}">
                            <textarea name="message" class="form-control form-control-sm" rows="2"
                                      placeholder="{{ __('site.booking.message') }}">{{ old('message') }}</textarea>

                            <label class="d-flex gap-2 small">
                                <input type="checkbox" name="kvkk" value="1" class="form-check-input mt-1" required>
                                <span>{{ __('site.booking.kvkk') }}</span>
                            </label>
                            <label class="d-flex gap-2 small">
                                <input type="checkbox" name="whatsapp_consent" value="1" class="form-check-input mt-1" required>
                                <span>{{ __('site.booking.whatsapp_consent') }}</span>
                            </label>

                            <div class="estimate-note">{{ __('site.booking.estimate_note') }}</div>

                            <button type="submit" class="btn btn-brass w-100">
                                {{ __('site.booking.submit') }}
                            </button>
                            <p class="text-center small text-muted-2 mb-0">
                                <i class="bi bi-shield-check me-1"></i>{{ __('site.booking.no_payment') }}
                            </p>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Benzer yatlar --}}
    @if ($similar->isNotEmpty())
        <section class="mt-5">
            <h2 class="section-title mb-3">{{ __('site.detail.similar') }}</h2>
            <div class="row g-3">
                @foreach ($similar as $other)
                    <div class="col-12 col-sm-6 col-lg-4">
                        @include('partials.yacht-card', ['yacht' => $other])
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>

@push('scripts')
<script>
    document.querySelectorAll('.gallery-thumbs img').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            document.getElementById('gallery-main-img').src = this.dataset.full;
            document.querySelectorAll('.gallery-thumbs img').forEach(function (t) { t.classList.remove('active'); });
            this.classList.add('active');
        });
    });
</script>
@endpush

@endsection

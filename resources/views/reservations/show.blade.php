@extends('layouts.site')

@php
    $locale = app()->getLocale();
    $status = $reservation->status;
@endphp

@section('title', $reservation->code.' — '.setting('site_name', config('app.name')))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <div class="panel">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <div class="small text-muted-2">{{ __('site.lookup.code') }}</div>
                        <h1 class="h4 mb-0">{{ $reservation->code }}</h1>
                    </div>
                    <span class="badge fs-6 {{ match ($status->color()) {
                        'success' => 'text-bg-success',
                        'warning' => 'text-bg-warning',
                        'danger' => 'text-bg-danger',
                        default => 'text-bg-secondary',
                    } }}">{{ $status->label() }}</span>
                </div>

                <div class="spec-grid">
                    <div>
                        <div class="k">{{ __('site.nav.yachts') }}</div>
                        <div class="v">
                            <a href="{{ lroute('yachts.show', $reservation->yacht->slug) }}">
                                {{ $reservation->yacht->getTranslation('name', $locale) }}
                            </a>
                        </div>
                    </div>
                    <div>
                        <div class="k">{{ __('site.booking.start') }}</div>
                        <div class="v">{{ $reservation->starts_at->format('d.m.Y H:i') }}</div>
                    </div>
                    <div>
                        <div class="k">{{ __('site.booking.end') }}</div>
                        <div class="v">{{ $reservation->ends_at->format('d.m.Y H:i') }}</div>
                    </div>
                    <div>
                        <div class="k">{{ __('site.booking.guests') }}</div>
                        <div class="v">{{ $reservation->guests }}</div>
                    </div>
                    <div>
                        <div class="k">{{ __('site.booking.estimate') }}</div>
                        <div class="v">{{ money($reservation->estimated_total, $reservation->currency) }}</div>
                    </div>
                </div>

                <div class="estimate-note mt-3">{{ __('site.booking.estimate_note') }}</div>

                @if ($reservation->reject_reason)
                    <div class="alert alert-warning small mt-3 mb-0">{{ $reservation->reject_reason }}</div>
                @endif
            </div>

            <div class="panel">
                <h2 class="h6">{{ __('site.contact.title') }}</h2>
                <p class="small text-muted-2 mb-0">
                    {{ __('site.footer.tagline') }}
                    @if (setting('site_phone'))
                        <br><i class="bi bi-telephone me-1"></i>{{ setting('site_phone') }}
                    @endif
                </p>
            </div>

        </div>
    </div>
</div>
@endsection

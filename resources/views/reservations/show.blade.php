@extends('layouts.site')

@section('robots', 'noindex, nofollow')

@php
    $locale = app()->getLocale();
    $status = $reservation->status;
    $badge = match ($status->color()) {
        'success' => 'badge badge-ok',
        'warning' => 'badge badge-warn',
        'danger' => 'badge badge-danger',
        default => 'badge badge-soft',
    };
@endphp

@section('title', $reservation->code.' — '.setting('site_name', config('app.name')))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-14 sm:px-6">

    <div class="panel">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="label mb-0">{{ __('site.lookup.code') }}</p>
                <h1 class="font-mono text-2xl font-bold">{{ $reservation->code }}</h1>
            </div>
            <span class="{{ $badge }} text-sm">{{ $status->label() }}</span>
        </div>

        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            @foreach ([
                __('site.nav.yachts') => $reservation->yacht->getTranslation('name', $locale),
                __('site.booking.start') => $reservation->starts_at->format('d.m.Y H:i'),
                __('site.booking.end') => $reservation->ends_at->format('d.m.Y H:i'),
                __('site.booking.guests') => $reservation->guests,
                __('site.booking.estimate') => money($reservation->estimated_total, $reservation->currency),
            ] as $label => $value)
                <div class="rounded-lg bg-sea-50 px-3 py-2.5">
                    <dt class="text-[10px] uppercase tracking-wider text-sea-500">{{ $label }}</dt>
                    <dd class="mt-0.5 font-semibold">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <p class="mt-5 rounded-lg border-l-4 border-brass-500 bg-brass-50 px-4 py-2.5 text-xs text-brass-800">
            {{ __('site.booking.estimate_note') }}
        </p>

        @if ($reservation->reject_reason)
            <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ $reservation->reject_reason }}
            </p>
        @endif
    </div>

    @if ($reservation->hasCancelRequest())
        <div class="panel mt-5">
            <h2 class="mb-1 text-lg font-bold">{{ __('site.booking.cancel_request') }}</h2>
            <p class="text-sm text-sea-600">
                <i class="bi bi-hourglass-split text-brass-600"></i> {{ __('site.booking.cancel_pending') }}
            </p>
        </div>
    @elseif ($reservation->canRequestCancellation())
        <div class="panel mt-5">
            <h2 class="mb-3 text-lg font-bold">{{ __('site.booking.cancel_request') }}</h2>
            <form method="POST" action="{{ lroute('reservation.cancel-request', ['code' => $reservation->code]) }}"
                  class="space-y-3">
                @csrf
                <input type="hidden" name="token" value="{{ $reservation->access_token }}">
                <textarea name="reason" rows="2" class="field" required
                          placeholder="{{ __('site.booking.cancel_reason') }}"></textarea>
                <button type="submit" class="btn border border-red-200 bg-white text-red-700 hover:bg-red-50">
                    {{ __('site.booking.cancel_request') }}
                </button>
            </form>
        </div>
    @endif

    <div class="panel mt-5 text-sm text-sea-600">
        {{ __('site.footer.tagline') }}
        @if (setting('site_phone'))
            <p class="mt-2"><i class="bi bi-telephone mr-2 text-brass-600"></i>{{ setting('site_phone') }}</p>
        @endif
    </div>
</div>
@endsection

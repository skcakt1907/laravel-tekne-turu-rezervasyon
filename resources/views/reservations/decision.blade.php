@extends('layouts.site')

@section('robots', 'noindex, nofollow')

@php $locale = app()->getLocale(); @endphp

@section('title', $reservation->code.' — '.setting('site_name', config('app.name')))

@section('content')
<div class="mx-auto max-w-2xl px-4 py-14 sm:px-6">
    <h1 class="text-2xl font-bold">{{ $reservation->yacht->getTranslation('name', $locale) }}</h1>
    <p class="mb-6 font-mono text-sm text-sea-500">{{ $reservation->code }}</p>

    <div class="panel">
        <dl class="mb-5 grid grid-cols-2 gap-4 sm:grid-cols-3">
            @foreach ([
                __('site.booking.date') => $reservation->starts_at->format('d.m.Y'),
                __('site.booking.adults') => $reservation->adults,
                __('site.booking.children') => $reservation->children,
                __('site.booking.estimate') => money($reservation->estimated_total, $reservation->currency),
                __('site.booking.name') => $reservation->customer_name,
                __('site.booking.phone') => $reservation->customer_phone,
            ] as $label => $value)
                <div class="rounded-lg bg-sea-50 px-3 py-2.5">
                    <dt class="text-[10px] uppercase tracking-wider text-sea-500">{{ $label }}</dt>
                    <dd class="mt-0.5 font-semibold">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        @if ($reservation->message)
            <p class="mb-5 rounded-lg bg-sea-50 px-4 py-3 text-sm text-sea-700">{{ $reservation->message }}</p>
        @endif

        <form method="POST"
              action="{{ lroute('reservation.decide', ['code' => $reservation->code, 'token' => $reservation->access_token]) }}"
              class="space-y-3">
            @csrf
            <textarea name="reason" rows="2" class="field" placeholder="{{ __('site.booking.message') }}"></textarea>
            <div class="flex gap-3">
                <button type="submit" name="decision" value="approve" class="btn btn-brass flex-1">
                    <i class="bi bi-check2"></i>{{ __('site.decision.approve') }}
                </button>
                <button type="submit" name="decision" value="reject"
                        class="btn flex-1 border border-red-200 bg-white text-red-700 hover:bg-red-50">
                    <i class="bi bi-x"></i>{{ __('site.decision.reject') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

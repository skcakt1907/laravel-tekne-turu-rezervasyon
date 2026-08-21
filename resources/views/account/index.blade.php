@extends('layouts.site')

@section('robots', 'noindex, nofollow')

@php $locale = app()->getLocale(); @endphp

@section('title', __('site.account.title').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="mx-auto max-w-5xl px-4 py-14 sm:px-6">

    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">{{ __('site.account.title') }}</h1>
            <p class="mt-1 text-sm text-sea-600">{{ auth()->user()->name }} · {{ auth()->user()->email }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ lroute('account.profile') }}" class="btn btn-ghost btn-sm">
                <i class="bi bi-person"></i>{{ __('site.account.profile') }}
            </a>
            <form method="POST" action="{{ lroute('account.logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">
                    <i class="bi bi-box-arrow-right"></i>{{ __('site.account.logout') }}
                </button>
            </form>
        </div>
    </div>

    @if ($reservations->isEmpty())
        <div class="panel py-16 text-center">
            <i class="bi bi-calendar-x mb-3 block text-4xl text-sea-300"></i>
            <p class="font-semibold">{{ __('site.account.empty') }}</p>
            <p class="mt-1 text-sm text-sea-500">{{ __('site.account.empty_hint') }}</p>
            <a href="{{ lroute('yachts.index') }}" class="btn btn-brass btn-sm mt-5">{{ __('site.home.all_yachts') }}</a>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($reservations as $reservation)
                <div class="panel flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="mb-1.5 flex flex-wrap items-center gap-2">
                            <h2 class="font-serif text-lg font-semibold">
                                {{ $reservation->yacht->getTranslation('name', $locale) }}
                            </h2>
                            <span class="{{ match ($reservation->status->color()) {
                                'success' => 'badge badge-ok',
                                'warning' => 'badge badge-warn',
                                'danger' => 'badge badge-danger',
                                default => 'badge badge-soft',
                            } }}">{{ $reservation->status->label() }}</span>
                            @if ($reservation->hasCancelRequest())
                                <span class="badge badge-soft">{{ __('site.booking.cancel_pending') }}</span>
                            @endif
                        </div>
                        <p class="text-sm text-sea-600">
                            <span class="font-mono text-xs">{{ $reservation->code }}</span>
                            <span class="mx-1 text-sea-300">·</span>
                            {{ $reservation->starts_at->format('d.m.Y H:i') }} — {{ $reservation->ends_at->format('d.m.Y H:i') }}
                            <span class="mx-1 text-sea-300">·</span>
                            {{ __('site.card.guests', ['count' => $reservation->guests]) }}
                        </p>
                    </div>

                    <div class="text-right">
                        <div class="font-semibold">{{ money($reservation->estimated_total, $reservation->currency) }}</div>
                        <a class="text-sm text-brass-700 hover:underline"
                           href="{{ lroute('reservation.show', ['code' => $reservation->code]).'?token='.$reservation->access_token }}">
                            {{ __('mail.common.view_reservation') }} <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">{{ $reservations->links() }}</div>
    @endif
</div>
@endsection

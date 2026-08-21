@extends('layouts.site')

@section('robots', 'noindex, nofollow')

@php $locale = app()->getLocale(); @endphp

@section('title', __('site.account.title').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="container py-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ __('site.account.title') }}</h1>
            <p class="text-muted-2 mb-0">{{ auth()->user()->name }} · {{ auth()->user()->email }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ lroute('account.profile') }}" class="btn btn-outline-sea btn-sm">
                <i class="bi bi-person me-1"></i>{{ __('site.account.profile') }}
            </a>
            <form method="POST" action="{{ lroute('account.logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-sea btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>{{ __('site.account.logout') }}
                </button>
            </form>
        </div>
    </div>

    @if ($reservations->isEmpty())
        <div class="panel empty-state">
            <i class="bi bi-calendar-x d-block mb-2"></i>
            <p class="fw-semibold mb-1">{{ __('site.account.empty') }}</p>
            <p class="small mb-3">{{ __('site.account.empty_hint') }}</p>
            <a href="{{ lroute('yachts.index') }}" class="btn btn-brass btn-sm">{{ __('site.home.all_yachts') }}</a>
        </div>
    @else
        <div class="vstack gap-3">
            @foreach ($reservations as $reservation)
                <div class="panel">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h2 class="h6 mb-0">
                                    {{ $reservation->yacht->getTranslation('name', $locale) }}
                                </h2>
                                <span class="badge {{ match ($reservation->status->color()) {
                                    'success' => 'text-bg-success',
                                    'warning' => 'text-bg-warning',
                                    'danger' => 'text-bg-danger',
                                    default => 'text-bg-secondary',
                                } }}">{{ $reservation->status->label() }}</span>
                                @if ($reservation->hasCancelRequest())
                                    <span class="badge badge-soft">{{ __('site.booking.cancel_pending') }}</span>
                                @endif
                            </div>
                            <div class="small text-muted-2">
                                <span class="font-monospace">{{ $reservation->code }}</span>
                                <span class="mx-1">·</span>
                                {{ $reservation->starts_at->format('d.m.Y H:i') }} — {{ $reservation->ends_at->format('d.m.Y H:i') }}
                                <span class="mx-1">·</span>
                                {{ __('site.card.guests', ['count' => $reservation->guests]) }}
                            </div>
                        </div>

                        <div class="text-lg-end">
                            <div class="fw-bold">{{ money($reservation->estimated_total, $reservation->currency) }}</div>
                            <a class="small" href="{{ lroute('reservation.show', ['code' => $reservation->code]).'?token='.$reservation->access_token }}">
                                {{ __('mail.common.view_reservation') }} <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $reservations->links() }}</div>
    @endif

</div>
@endsection

@extends('layouts.site')

@section('robots', 'noindex, nofollow')

@php $locale = app()->getLocale(); @endphp

@section('title', $reservation->code.' — '.setting('site_name', config('app.name')))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-6">
            <h1 class="h4 mb-1">{{ $reservation->yacht->getTranslation('name', $locale) }}</h1>
            <p class="text-muted-2">{{ $reservation->code }}</p>

            <div class="panel">
                <div class="spec-grid mb-3">
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
                    <div>
                        <div class="k">{{ __('site.booking.name') }}</div>
                        <div class="v">{{ $reservation->customer_name }}</div>
                    </div>
                    <div>
                        <div class="k">{{ __('site.booking.phone') }}</div>
                        <div class="v">{{ $reservation->customer_phone }}</div>
                    </div>
                </div>

                @if ($reservation->message)
                    <p class="small text-muted-2">{{ $reservation->message }}</p>
                @endif

                <form method="POST" action="{{ lroute('reservation.decide', ['code' => $reservation->code, 'token' => $reservation->access_token]) }}"
                      class="vstack gap-2">
                    @csrf
                    <textarea name="reason" rows="2" class="form-control form-control-sm"
                              placeholder="{{ __('site.booking.message') }}"></textarea>
                    <div class="d-flex gap-2">
                        <button type="submit" name="decision" value="approve" class="btn btn-brass flex-fill">
                            <i class="bi bi-check2 me-1"></i>Onayla
                        </button>
                        <button type="submit" name="decision" value="reject" class="btn btn-outline-danger flex-fill">
                            <i class="bi bi-x me-1"></i>Reddet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

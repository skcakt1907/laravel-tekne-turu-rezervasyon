@extends('emails.layout')

@section('body')
    @include('emails.partials.body', [
        'greetName' => $reservation->owner?->name ?? '',
        'template' => 'cancel_requested_owner',
        'showCustomer' => true,
        'notice' => false,
        'buttonUrl' => url('/yonetim/reservations'),
        'buttonLabel' => __('mail.common.view_reservation'),
        'extraNote' => $reservation->cancel_request_reason,
    ])
@endsection

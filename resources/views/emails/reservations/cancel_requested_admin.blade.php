@extends('emails.layout')

@section('body')
    @include('emails.partials.body', [
        'greetName' => setting('site_name', config('app.name')),
        'template' => 'cancel_requested_admin',
        'showCustomer' => true,
        'notice' => false,
        'buttonUrl' => url('/yonetim/reservations'),
        'buttonLabel' => __('mail.common.view_reservation'),
        'extraNote' => $reservation->cancel_request_reason,
    ])
@endsection

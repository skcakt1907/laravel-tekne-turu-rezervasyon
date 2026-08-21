@extends('emails.layout')

@section('body')
    @include('emails.partials.body', [
        'greetName' => $reservation->customer_name,
        'template' => 'trip_reminder_customer',
        'showCustomer' => false,
        'notice' => false,
        'buttonUrl' => lroute('reservation.show', ['code' => $reservation->code]).'?token='.$reservation->access_token,
        'buttonLabel' => __('mail.common.view_reservation'),
    ])
@endsection

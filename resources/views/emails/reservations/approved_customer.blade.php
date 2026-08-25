@extends('emails.layout')

@section('body')
    @include('emails.partials.body', [
        'greetName' => $reservation->customer_name,
        'template' => 'approved_customer',
        'showCustomer' => false,
        'notice' => true,
        'buttonUrl' => lroute('reservation.show', ['code' => $reservation->code]).'?token='.$reservation->access_token,
        'buttonLabel' => __('mail.common.view_reservation'),
        'qrCode' => $reservation->qrCodeDataUri(),
    ])
@endsection

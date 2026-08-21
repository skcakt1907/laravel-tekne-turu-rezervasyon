@extends('emails.layout')

@section('body')
    @include('emails.partials.body', [
        'greetName' => $reservation->customer_name,
        'template' => 'cancelled_customer',
        'showCustomer' => false,
        'notice' => false,
    ])
@endsection

@extends('emails.layout')

@section('body')
    @include('emails.partials.body', [
        'greetName' => $reservation->customer_name,
        'template' => 'rejected_customer',
        'showCustomer' => false,
        'notice' => false,
        'extraNote' => $reservation->reject_reason,
    ])
@endsection

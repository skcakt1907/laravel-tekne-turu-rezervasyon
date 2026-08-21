@extends('emails.layout')

@section('body')
    @include('emails.partials.body', [
        'greetName' => $reservation->owner?->name ?? '',
        'template' => 'approved_owner',
        'showCustomer' => true,
        'notice' => false,
        'buttonUrl' => url('/yonetim'),
        'buttonLabel' => __('mail.common.view_reservation'),
    ])
@endsection

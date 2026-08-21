@extends('emails.layout')

@section('body')
    @include('emails.partials.body', [
        'greetName' => $reservation->owner?->name ?? '',
        'template' => 'pending_reminder_owner',
        'showCustomer' => true,
        'notice' => true,
        'buttonUrl' => lroute('reservation.decision', ['code' => $reservation->code, 'token' => $reservation->access_token]),
        'buttonLabel' => __('mail.common.respond'),
    ])
@endsection

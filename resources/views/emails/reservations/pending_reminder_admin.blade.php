@extends('emails.layout')

@section('body')
    @include('emails.partials.body', [
        'greetName' => setting('site_name', config('app.name')),
        'template' => 'pending_reminder_admin',
        'showCustomer' => true,
        'notice' => true,
        'buttonUrl' => lroute('reservation.decision', ['code' => $reservation->code, 'token' => $reservation->access_token]),
        'buttonLabel' => __('mail.common.respond'),
    ])
@endsection

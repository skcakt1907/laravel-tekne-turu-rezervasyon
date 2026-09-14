@extends('emails.layout')

@section('body')
    @include('emails.partials.body', [
        'greetName' => setting('site_name', config('app.name')),
        'template' => 'cancelled_admin',
        'showCustomer' => true,
        'notice' => false,
        'buttonUrl' => url('/yonetim'),
        'buttonLabel' => __('mail.common.view_reservation'),
    ])
@endsection

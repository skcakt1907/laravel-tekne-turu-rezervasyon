@extends('emails.layout')

@section('body')
    @include('emails.partials.body', [
        'greetName' => setting('site_name', config('app.name')),
        'template' => 'request_new_admin',
        'showCustomer' => true,
        'notice' => false,
        'buttonUrl' => url('/yonetim'),
        'buttonLabel' => __('mail.common.respond'),
    ])
@endsection

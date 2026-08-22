@extends('layouts.site')

@section('robots', 'noindex, nofollow')
@section('title', __('site.password.title').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <h1 class="mb-2 text-3xl font-bold">{{ __('site.password.title') }}</h1>
    <p class="mb-6 text-sm text-sea-600">{{ __('site.password.sub') }}</p>

    <form method="POST" action="{{ lroute('password.email') }}" class="panel space-y-4">
        @csrf
        <div>
            <label class="label" for="f-email">{{ __('site.account.email') }}</label>
            <input type="email" name="email" id="f-email" class="field" value="{{ old('email') }}" required autofocus>
        </div>
        <button type="submit" class="btn btn-brass w-full">{{ __('site.password.send') }}</button>
    </form>

    <p class="mt-5 text-sm">
        <a href="{{ lroute('account.login') }}" class="text-brass-700 hover:underline">
            <i class="bi bi-arrow-left"></i> {{ __('site.password.back') }}
        </a>
    </p>
</div>
@endsection

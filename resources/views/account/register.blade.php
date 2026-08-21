@extends('layouts.site')

@section('robots', 'noindex, nofollow')
@section('title', __('site.account.register').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="mx-auto max-w-lg px-4 py-16 sm:px-6">
    <h1 class="mb-6 text-3xl font-bold">{{ __('site.account.register') }}</h1>

    <form method="POST" action="{{ lroute('account.register.submit') }}" class="panel space-y-4">
        @csrf
        <div>
            <label class="label" for="r-name">{{ __('site.account.name') }}</label>
            <input type="text" name="name" id="r-name" class="field" value="{{ old('name') }}" required>
        </div>
        <div>
            <label class="label" for="r-email">{{ __('site.account.email') }}</label>
            <input type="email" name="email" id="r-email" class="field" value="{{ old('email') }}" required>
        </div>
        <div>
            <label class="label" for="r-phone">{{ __('site.account.phone') }}</label>
            <input type="tel" name="phone" id="r-phone" class="field" value="{{ old('phone') }}">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="label" for="r-pass">{{ __('site.account.password') }}</label>
                <input type="password" name="password" id="r-pass" class="field" required>
            </div>
            <div>
                <label class="label" for="r-pass2">{{ __('site.account.password_confirm') }}</label>
                <input type="password" name="password_confirmation" id="r-pass2" class="field" required>
            </div>
        </div>
        <button type="submit" class="btn btn-brass w-full">{{ __('site.account.register') }}</button>
    </form>

    <p class="mt-5 text-sm text-sea-600">
        {{ __('site.account.have_account') }}
        <a href="{{ lroute('account.login') }}" class="font-semibold text-brass-700 hover:underline">{{ __('site.account.login') }}</a>
    </p>
</div>
@endsection

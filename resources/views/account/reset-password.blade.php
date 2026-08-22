@extends('layouts.site')

@section('robots', 'noindex, nofollow')
@section('title', __('site.password.new_title').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <h1 class="mb-6 text-3xl font-bold">{{ __('site.password.new_title') }}</h1>

    <form method="POST" action="{{ lroute('password.update') }}" class="panel space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label class="label" for="r-email">{{ __('site.account.email') }}</label>
            <input type="email" name="email" id="r-email" class="field"
                   value="{{ old('email', $email) }}" required>
        </div>
        <div>
            <label class="label" for="r-pass">{{ __('site.password.new_password') }}</label>
            <input type="password" name="password" id="r-pass" class="field" required autocomplete="new-password">
        </div>
        <div>
            <label class="label" for="r-pass2">{{ __('site.password.confirm') }}</label>
            <input type="password" name="password_confirmation" id="r-pass2" class="field" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-brass w-full">{{ __('site.password.submit') }}</button>
    </form>
</div>
@endsection

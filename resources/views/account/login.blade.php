@extends('layouts.site')

@section('robots', 'noindex, nofollow')
@section('title', __('site.account.login').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <h1 class="mb-6 text-3xl font-bold">{{ __('site.account.login') }}</h1>

    <form method="POST" action="{{ lroute('account.login.submit') }}" class="panel space-y-4">
        @csrf
        <div>
            <label class="label" for="l-email">{{ __('site.account.email') }}</label>
            <input type="email" name="email" id="l-email" class="field" value="{{ old('email') }}" required autofocus>
        </div>
        <div>
            <label class="label" for="l-pass">{{ __('site.account.password') }}</label>
            <input type="password" name="password" id="l-pass" class="field" required>
        </div>
        <label class="flex cursor-pointer items-center gap-2 text-sm text-sea-600">
            <input type="checkbox" name="remember" value="1"
                   class="h-4 w-4 rounded border-sea-300 text-brass-600 focus:ring-brass-300">
            {{ __('site.account.remember') }}
        </label>
        <div class="flex items-center justify-between">
            <a href="{{ lroute('password.request') }}" class="text-xs text-brass-700 hover:underline">
                {{ __('site.password.forgot') }}
            </a>
        </div>
        <button type="submit" class="btn btn-brass w-full">{{ __('site.account.login') }}</button>
    </form>

    <p class="mt-5 text-sm text-sea-600">
        {{ __('site.account.no_account') }}
        <a href="{{ lroute('account.register') }}" class="font-semibold text-brass-700 hover:underline">{{ __('site.account.register') }}</a>
    </p>
    <p class="mt-1 text-xs text-sea-500">{{ __('site.account.optional_note') }}</p>
</div>
@endsection

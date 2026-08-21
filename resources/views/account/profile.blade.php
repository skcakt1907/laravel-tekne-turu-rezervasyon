@extends('layouts.site')

@section('robots', 'noindex, nofollow')
@section('title', __('site.account.profile').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="mx-auto max-w-2xl px-4 py-14 sm:px-6">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold">{{ __('site.account.profile') }}</h1>
        <a href="{{ lroute('account') }}" class="btn btn-ghost btn-sm">
            <i class="bi bi-arrow-left"></i>{{ __('site.account.title') }}
        </a>
    </div>

    <form method="POST" action="{{ lroute('account.profile.update') }}" class="panel space-y-4">
        @csrf
        <div>
            <label class="label" for="p-name">{{ __('site.account.name') }}</label>
            <input type="text" name="name" id="p-name" class="field" value="{{ old('name', auth()->user()->name) }}" required>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="label" for="p-phone">{{ __('site.account.phone') }}</label>
                <input type="tel" name="phone" id="p-phone" class="field" value="{{ old('phone', auth()->user()->phone) }}">
            </div>
            <div>
                <label class="label" for="p-wa">{{ __('site.account.whatsapp') }}</label>
                <input type="tel" name="whatsapp_no" id="p-wa" class="field" value="{{ old('whatsapp_no', auth()->user()->whatsapp_no) }}">
            </div>
        </div>

        <hr class="border-sea-100">

        <div>
            <label class="label" for="p-current">{{ __('site.account.current_password') }}</label>
            <input type="password" name="current_password" id="p-current" class="field" autocomplete="current-password">
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="label" for="p-new">{{ __('site.account.new_password') }}</label>
                <input type="password" name="password" id="p-new" class="field" autocomplete="new-password">
            </div>
            <div>
                <label class="label" for="p-new2">{{ __('site.account.password_confirm') }}</label>
                <input type="password" name="password_confirmation" id="p-new2" class="field" autocomplete="new-password">
            </div>
        </div>

        <button type="submit" class="btn btn-brass">{{ __('site.account.save') }}</button>
    </form>
</div>
@endsection

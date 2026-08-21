@extends('layouts.site')

@section('robots', 'noindex, nofollow')
@section('title', __('site.lookup.title').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <h1 class="mb-2 text-3xl font-bold">{{ __('site.lookup.title') }}</h1>
    <p class="mb-6 text-sm text-sea-600">{{ __('site.lookup.sub') }}</p>

    <form method="POST" action="{{ lroute('reservation.lookup.submit') }}" class="panel space-y-4">
        @csrf
        <div>
            <label class="label" for="q-code">{{ __('site.lookup.code') }}</label>
            <input type="text" name="code" id="q-code" class="field font-mono" placeholder="YK-26-XXXXX"
                   value="{{ old('code') }}" required autofocus>
        </div>
        <div>
            <label class="label" for="q-email">{{ __('site.lookup.email') }}</label>
            <input type="email" name="email" id="q-email" class="field" value="{{ old('email') }}" required>
        </div>
        <button type="submit" class="btn btn-brass w-full">{{ __('site.lookup.submit') }}</button>
    </form>
</div>
@endsection

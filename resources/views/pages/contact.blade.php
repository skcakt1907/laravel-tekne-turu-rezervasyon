@extends('layouts.site')

@section('title', __('site.contact.title').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="mx-auto max-w-5xl px-4 py-14 sm:px-6">
    <h1 class="mb-8 text-3xl font-bold sm:text-4xl">{{ __('site.contact.title') }}</h1>

    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <form method="POST" action="{{ lroute('contact.store') }}" class="panel space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="c-name">{{ __('site.contact.name') }}</label>
                    <input type="text" name="name" id="c-name" class="field" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="label" for="c-email">{{ __('site.contact.email') }}</label>
                    <input type="email" name="email" id="c-email" class="field" value="{{ old('email') }}" required>
                </div>
                <div>
                    <label class="label" for="c-phone">{{ __('site.contact.phone') }}</label>
                    <input type="tel" name="phone" id="c-phone" class="field" value="{{ old('phone') }}">
                </div>
                <div>
                    <label class="label" for="c-subject">{{ __('site.contact.subject') }}</label>
                    <input type="text" name="subject" id="c-subject" class="field" value="{{ old('subject') }}">
                </div>
            </div>
            <div>
                <label class="label" for="c-message">{{ __('site.contact.message') }}</label>
                <textarea name="message" id="c-message" rows="6" class="field" required>{{ old('message') }}</textarea>
            </div>
            <button type="submit" class="btn btn-brass">{{ __('site.contact.submit') }}</button>
        </form>

        <aside class="panel h-fit space-y-3 text-sm">
            <h2 class="font-serif text-lg font-bold">{{ setting('site_name', config('app.name')) }}</h2>
            @if (setting('site_phone'))
                <p><i class="bi bi-telephone mr-2 text-brass-600"></i>{{ setting('site_phone') }}</p>
            @endif
            @if (setting('site_email'))
                <p><i class="bi bi-envelope mr-2 text-brass-600"></i>{{ setting('site_email') }}</p>
            @endif
            @if (setting('address'))
                <p><i class="bi bi-geo-alt mr-2 text-brass-600"></i>{{ setting('address') }}</p>
            @endif
        </aside>
    </div>
</div>
@endsection

@extends('layouts.site')

@section('title', __('site.contact.title').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="container py-5">
    <div class="row g-4 justify-content-center">
        <div class="col-12 col-lg-7">
            <h1 class="h2 mb-3">{{ __('site.contact.title') }}</h1>
            <div class="panel">
                <form method="POST" action="{{ lroute('contact.store') }}" class="vstack gap-3">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('site.contact.name') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('site.contact.email') }}</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('site.contact.phone') }}</label>
                            <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('site.contact.subject') }}</label>
                            <input type="text" name="subject" class="form-control" value="{{ old('subject') }}">
                        </div>
                    </div>
                    <div>
                        <label class="form-label small">{{ __('site.contact.message') }}</label>
                        <textarea name="message" rows="5" class="form-control" required>{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-brass align-self-start px-4">{{ __('site.contact.submit') }}</button>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="panel">
                <h2 class="h6">{{ setting('site_name', config('app.name')) }}</h2>
                @if (setting('site_phone'))
                    <p class="mb-2"><i class="bi bi-telephone me-2 text-muted-2"></i>{{ setting('site_phone') }}</p>
                @endif
                @if (setting('site_email'))
                    <p class="mb-2"><i class="bi bi-envelope me-2 text-muted-2"></i>{{ setting('site_email') }}</p>
                @endif
                @if (setting('address'))
                    <p class="mb-0"><i class="bi bi-geo-alt me-2 text-muted-2"></i>{{ setting('address') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

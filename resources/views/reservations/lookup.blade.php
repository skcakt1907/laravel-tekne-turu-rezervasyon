@extends('layouts.site')

@section('title', __('site.lookup.title').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-5">
            <h1 class="h3 mb-1">{{ __('site.lookup.title') }}</h1>
            <p class="text-muted-2">{{ __('site.lookup.sub') }}</p>

            <div class="panel">
                <form method="POST" action="{{ lroute('reservation.lookup.submit') }}" class="vstack gap-3">
                    @csrf
                    <div>
                        <label class="form-label small">{{ __('site.lookup.code') }}</label>
                        <input type="text" name="code" class="form-control" placeholder="YK-26-XXXXX"
                               value="{{ old('code') }}" required>
                    </div>
                    <div>
                        <label class="form-label small">{{ __('site.lookup.email') }}</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <button type="submit" class="btn btn-brass">{{ __('site.lookup.submit') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.site')

@section('title', __('site.account.login').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-4">
            <h1 class="h3 mb-3">{{ __('site.account.login') }}</h1>

            <div class="panel">
                <form method="POST" action="{{ lroute('account.login.submit') }}" class="vstack gap-3">
                    @csrf
                    <div>
                        <label class="form-label small">{{ __('site.account.email') }}</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                    </div>
                    <div>
                        <label class="form-label small">{{ __('site.account.password') }}</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <label class="d-flex align-items-center gap-2 small">
                        <input type="checkbox" name="remember" value="1" class="form-check-input mt-0">
                        {{ __('site.account.remember') }}
                    </label>
                    <button type="submit" class="btn btn-brass">{{ __('site.account.login') }}</button>
                </form>
            </div>

            <p class="small text-muted-2 mt-3 mb-1">
                {{ __('site.account.no_account') }}
                <a href="{{ lroute('account.register') }}">{{ __('site.account.register') }}</a>
            </p>
            <p class="small text-muted-2 mb-0">{{ __('site.account.optional_note') }}</p>
        </div>
    </div>
</div>
@endsection

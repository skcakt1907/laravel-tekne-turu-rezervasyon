@extends('layouts.site')

@section('robots', 'noindex, nofollow')

@section('title', __('site.account.register').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-5">
            <h1 class="h3 mb-3">{{ __('site.account.register') }}</h1>

            <div class="panel">
                <form method="POST" action="{{ lroute('account.register.submit') }}" class="vstack gap-3">
                    @csrf
                    <div>
                        <label class="form-label small">{{ __('site.account.name') }}</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div>
                        <label class="form-label small">{{ __('site.account.email') }}</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div>
                        <label class="form-label small">{{ __('site.account.phone') }}</label>
                        <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">{{ __('site.account.password') }}</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">{{ __('site.account.password_confirm') }}</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-brass">{{ __('site.account.register') }}</button>
                </form>
            </div>

            <p class="small text-muted-2 mt-3">
                {{ __('site.account.have_account') }}
                <a href="{{ lroute('account.login') }}">{{ __('site.account.login') }}</a>
            </p>
        </div>
    </div>
</div>
@endsection

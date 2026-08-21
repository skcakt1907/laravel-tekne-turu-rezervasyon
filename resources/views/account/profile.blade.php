@extends('layouts.site')

@section('robots', 'noindex, nofollow')

@section('title', __('site.account.profile').' — '.setting('site_name', config('app.name')))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h3 mb-0">{{ __('site.account.profile') }}</h1>
                <a href="{{ lroute('account') }}" class="btn btn-outline-sea btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>{{ __('site.account.title') }}
                </a>
            </div>

            <div class="panel">
                <form method="POST" action="{{ lroute('account.profile.update') }}" class="vstack gap-3">
                    @csrf
                    <div>
                        <label class="form-label small">{{ __('site.account.name') }}</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', auth()->user()->name) }}" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">{{ __('site.account.phone') }}</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="{{ old('phone', auth()->user()->phone) }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">{{ __('site.account.whatsapp') }}</label>
                            <input type="tel" name="whatsapp_no" class="form-control"
                                   value="{{ old('whatsapp_no', auth()->user()->whatsapp_no) }}">
                        </div>
                    </div>

                    <hr class="my-1">

                    <div>
                        <label class="form-label small">{{ __('site.account.current_password') }}</label>
                        <input type="password" name="current_password" class="form-control" autocomplete="current-password">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">{{ __('site.account.new_password') }}</label>
                            <input type="password" name="password" class="form-control" autocomplete="new-password">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">{{ __('site.account.password_confirm') }}</label>
                            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-brass align-self-start px-4">{{ __('site.account.save') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

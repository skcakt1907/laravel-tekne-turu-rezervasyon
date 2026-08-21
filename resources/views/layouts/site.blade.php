<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', setting('site_name', config('app.name')))</title>
    <meta name="description" content="@yield('meta_description', setting('site_description', ''))">
    <link rel="canonical" href="{{ url()->current() }}">

    @foreach (config('yacht.locales') as $code => $cfg)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ locale_url($code) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ locale_url(array_key_first(config('yacht.locales'))) }}">

    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', setting('site_name', config('app.name')))">
    <meta property="og:description" content="@yield('meta_description', '')">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif

    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/site.css') }}?v={{ @filemtime(public_path('css/site.css')) }}" rel="stylesheet">

    @stack('head')
</head>
<body>

<header class="site-header">
    <nav class="navbar navbar-expand-lg py-2">
        <div class="container">
            <a class="navbar-brand" href="{{ lroute('home') }}">
                <i class="bi bi-life-preserver text-warning me-1"></i>{{ setting('site_name', config('app.name')) }}
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('yachts.*') ? 'active' : '' }}"
                           href="{{ lroute('yachts.index') }}">{{ __('site.nav.yachts') }}</a>
                    </li>
                    @foreach ($navPorts as $port)
                        @if ($loop->first)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{ __('site.nav.destinations') }}</a>
                                <ul class="dropdown-menu">
                        @endif
                                    <li>
                                        <a class="dropdown-item" href="{{ lroute('locations.show', $port->slug) }}">
                                            {{ $port->getTranslation('name', app()->getLocale()) }}
                                        </a>
                                    </li>
                        @if ($loop->last)
                                </ul>
                            </li>
                        @endif
                    @endforeach
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reservation.lookup') ? 'active' : '' }}"
                           href="{{ lroute('reservation.lookup') }}">{{ __('site.nav.lookup') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ lroute('contact') }}">{{ __('site.nav.contact') }}</a>
                    </li>
                    <li class="nav-item">
                        @auth
                            <a class="nav-link {{ request()->routeIs('account*') ? 'active' : '' }}"
                               href="{{ lroute('account') }}">
                                <i class="bi bi-person-circle me-1"></i>{{ __('site.account.title') }}
                            </a>
                        @else
                            <a class="nav-link" href="{{ lroute('account.login') }}">{{ __('site.account.login') }}</a>
                        @endauth
                    </li>
                    <li class="nav-item ms-lg-2 lang-switch d-flex align-items-center">
                        @foreach (config('yacht.locales') as $code => $cfg)
                            <a href="{{ locale_url($code) }}"
                               class="{{ app()->getLocale() === $code ? 'active' : '' }}"
                               hreflang="{{ $code }}">{{ $code }}</a>
                        @endforeach
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-brass btn-sm px-3" href="{{ lroute('owner.landing') }}">
                            {{ __('site.nav.list_your_yacht') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

@if (session('status'))
    <div class="container mt-3">
        <div class="alert alert-success d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-check-circle"></i>{{ session('status') }}
        </div>
    </div>
@endif

@if ($errors->any() && ! request()->routeIs('yachts.show'))
    <div class="container mt-3">
        <div class="alert alert-danger mb-0">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<main>
    @yield('content')
</main>

<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h6>{{ setting('site_name', config('app.name')) }}</h6>
                <p class="mb-2 small">{{ __('site.footer.tagline') }}</p>
                @if (setting('site_phone'))
                    <p class="mb-1 small"><i class="bi bi-telephone me-2"></i>{{ setting('site_phone') }}</p>
                @endif
                @if (setting('site_email'))
                    <p class="mb-0 small"><i class="bi bi-envelope me-2"></i>{{ setting('site_email') }}</p>
                @endif
            </div>

            <div class="col-6 col-lg-3">
                <h6>{{ __('site.footer.ports') }}</h6>
                <ul class="list-unstyled small mb-0">
                    @foreach ($navPorts as $port)
                        <li class="mb-1">
                            <a href="{{ lroute('locations.show', $port->slug) }}">
                                {{ $port->getTranslation('name', app()->getLocale()) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h6>{{ __('site.footer.company') }}</h6>
                <ul class="list-unstyled small mb-0">
                    @foreach ($footerPages as $page)
                        <li class="mb-1">
                            <a href="{{ lroute('pages.show', $page->slug) }}">
                                {{ $page->getTranslation('title', app()->getLocale()) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="col-lg-3">
                <h6>{{ __('site.footer.owners') }}</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-1"><a href="{{ lroute('owner.landing') }}">{{ __('site.nav.list_your_yacht') }}</a></li>
                    <li class="mb-1"><a href="/yat-sahibi">{{ __('site.footer.owner_login') }}</a></li>
                    <li class="mb-1"><a href="{{ lroute('reservation.lookup') }}">{{ __('site.nav.lookup') }}</a></li>
                </ul>
            </div>
        </div>

        <div class="foot-bottom d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; {{ date('Y') }} {{ setting('site_name', config('app.name')) }}</span>
            <span>{{ __('site.footer.no_payment') }}</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>

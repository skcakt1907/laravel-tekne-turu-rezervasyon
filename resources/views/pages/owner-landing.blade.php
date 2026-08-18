@extends('layouts.site')

@section('title', __('site.owner_landing.title').' — '.setting('site_name', config('app.name')))
@section('meta_description', __('site.owner_landing.sub'))

@section('content')
<section class="hero">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1 class="mb-3">{{ __('site.owner_landing.title') }}</h1>
                <p class="lead mb-4">{{ __('site.owner_landing.sub') }}</p>
                <a href="/yat-sahibi/register" class="btn btn-brass px-4">{{ __('site.owner_landing.cta') }}</a>
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="row g-3">
        @foreach (__('site.owner_benefits') as $benefit)
            <div class="col-12 col-md-6 col-lg-3">
                <div class="step-card">
                    <h3 class="h6 fw-bold mb-2">{{ $benefit[0] }}</h3>
                    <p class="small text-muted-2 mb-0">{{ $benefit[1] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>

@if ($faqs->isNotEmpty())
    <section class="container pb-5">
        <h2 class="section-title mb-3">{{ __('site.home.faq') }}</h2>
        <div class="accordion" id="owner-faq">
            @foreach ($faqs as $faq)
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#ofaq-{{ $faq->id }}">
                            {{ $faq->getTranslation('question', app()->getLocale()) }}
                        </button>
                    </h3>
                    <div id="ofaq-{{ $faq->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#owner-faq">
                        <div class="accordion-body text-muted-2">{{ $faq->getTranslation('answer', app()->getLocale()) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
@endsection

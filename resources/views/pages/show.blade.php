@extends('layouts.site')

@php $locale = app()->getLocale(); @endphp

@section('title', ($page->getTranslation('seo_title', $locale) ?: $page->getTranslation('title', $locale)).' — '.setting('site_name', config('app.name')))
@section('meta_description', $page->getTranslation('seo_description', $locale))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <h1 class="h2 mb-4">{{ $page->getTranslation('title', $locale) }}</h1>
            <div class="panel">
                {!! $page->getTranslation('body', $locale) !!}
            </div>
        </div>
    </div>
</div>
@endsection

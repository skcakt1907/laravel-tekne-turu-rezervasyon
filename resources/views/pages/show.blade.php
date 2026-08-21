@extends('layouts.site')

@php $locale = app()->getLocale(); @endphp

@section('title', ($page->getTranslation('seo_title', $locale) ?: $page->getTranslation('title', $locale)).' — '.setting('site_name', config('app.name')))
@section('meta_description', $page->getTranslation('seo_description', $locale))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
    <h1 class="mb-8 text-3xl font-bold sm:text-4xl">{{ $page->getTranslation('title', $locale) }}</h1>
    <article class="panel prose-site">
        {!! $page->getTranslation('body', $locale) !!}
    </article>
</div>
@endsection

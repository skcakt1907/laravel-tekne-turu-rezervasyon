@php
    $organization = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => setting('site_name', config('app.name')),
        'url' => url('/'),
        'email' => setting('site_email') ?: null,
        'telephone' => setting('site_phone') ?: null,
        'address' => setting('address') ? [
            '@type' => 'PostalAddress',
            'streetAddress' => setting('address'),
            'addressCountry' => 'TR',
        ] : null,
    ]);
@endphp
<script type="application/ld+json">{!! json_encode($organization, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

@php
    $locale = app()->getLocale();

    $schema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $yacht->getTranslation('name', $locale),
        'description' => \Illuminate\Support\Str::limit(
            strip_tags((string) $yacht->getTranslation('description', $locale)), 300
        ) ?: null,
        'image' => $yacht->coverUrl(),
        'category' => yacht_type_label($yacht->type),
        'brand' => $yacht->brand ? ['@type' => 'Brand', 'name' => $yacht->brand] : null,
        // Odeme alinmadigi icin Offer "InStock" degil, teklif/talep olarak isaretlenir
        'offers' => $yacht->price_from ? array_filter([
            '@type' => 'Offer',
            'price' => (float) $yacht->price_from,
            'priceCurrency' => $yacht->currency,
            'availability' => $yacht->is_open
                ? 'https://schema.org/PreOrder'
                : 'https://schema.org/OutOfStock',
            'url' => lroute('tours.show', $yacht->slug),
        ]) : null,
    ]);

    $breadcrumb = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_values(array_filter([
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('site.nav.yachts'), 'item' => lroute('tours.index')],
            $yacht->location ? [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $yacht->location->getTranslation('name', $locale),
                'item' => lroute('locations.show', $yacht->location->slug),
            ] : null,
            [
                '@type' => 'ListItem',
                'position' => $yacht->location ? 3 : 2,
                'name' => $yacht->getTranslation('name', $locale),
                'item' => lroute('tours.show', $yacht->slug),
            ],
        ])),
    ];
@endphp
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

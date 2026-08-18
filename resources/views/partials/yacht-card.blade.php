@php
    $locale = app()->getLocale();
    $unitKey = match ($yacht->price_from_unit) {
        'hour' => 'site.card.per_hour',
        'week' => 'site.card.per_week',
        default => 'site.card.per_day',
    };
@endphp

<a href="{{ lroute('yachts.show', $yacht->slug) }}" class="card-yacht d-block text-decoration-none text-reset">
    <div class="ratio ratio-4x3">
        <img src="{{ $yacht->coverUrl() ?? asset('images/yacht-placeholder.svg') }}"
             alt="{{ $yacht->getTranslation('name', $locale) }}" loading="lazy">
    </div>

    <div class="p-3">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
            <h3 class="card-title mb-0">{{ $yacht->getTranslation('name', $locale) }}</h3>
            @if ($yacht->is_featured)
                <span class="badge badge-brass">★</span>
            @endif
        </div>

        <div class="small text-muted-2 mb-2">
            <i class="bi bi-geo-alt me-1"></i>{{ $yacht->location?->getTranslation('name', $locale) ?? '—' }}
            <span class="mx-1">·</span>{{ yacht_type_label($yacht->type) }}
        </div>

        <div class="specs mb-3">
            @if ($yacht->capacity)
                <span><i class="bi bi-people me-1"></i>{{ __('site.card.guests', ['count' => $yacht->capacity]) }}</span>
            @endif
            @if ($yacht->cabins)
                <span><i class="bi bi-door-closed me-1"></i>{{ __('site.card.cabins', ['count' => $yacht->cabins]) }}</span>
            @endif
            @if ($yacht->length_m)
                <span><i class="bi bi-rulers me-1"></i>{{ rtrim(rtrim(number_format((float) $yacht->length_m, 1, ',', '.'), '0'), ',') }} m</span>
            @endif
            <span><i class="bi bi-person-badge me-1"></i>{{ $yacht->with_crew ? __('site.list.with_crew') : __('site.list.without_crew') }}</span>
        </div>

        @if ($yacht->price_from)
            <div class="price">
                {{ money($yacht->price_from, $yacht->currency) }}
                <small>/ {{ __($unitKey) }}</small>
            </div>
        @else
            <div class="text-muted-2 small">{{ __('site.detail.prices') }} —</div>
        @endif
    </div>
</a>

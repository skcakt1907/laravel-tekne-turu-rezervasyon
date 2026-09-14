@php
    $locale = app()->getLocale();
    $unitKey = match ($yacht->price_from_unit) {
        'hour' => 'site.card.per_hour',
        'week' => 'site.card.per_week',
        default => 'site.card.per_day',
    };
    $cover = $yacht->coverUrl();
@endphp

<a href="{{ lroute('tours.show', $yacht->slug) }}"
   class="group card flex h-full flex-col overflow-hidden transition duration-300 hover:-translate-y-1 hover:border-brass-300 hover:shadow-xl hover:shadow-sea-900/10">

    <div class="relative aspect-4/3 overflow-hidden {{ $cover ? '' : 'bg-placeholder' }}">
        @if ($cover)
            <img src="{{ $cover }}" alt="{{ $yacht->getTranslation('name', $locale) }}" loading="lazy"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        @else
            <div class="flex h-full items-center justify-center text-sea-300">
                <i class="bi bi-image text-3xl"></i>
            </div>
        @endif

        {{-- "Öne çıkan" rozeti kaldırıldı: tek firma olduğumuz için turların
             hepsi bizim, birini diğerinden ayırmanın anlamı kalmadı.
             is_featured alanı panelde duruyor, sıralama için kullanılıyor. --}}

        @if ($yacht->price_from)
            <div class="absolute bottom-3 right-3 rounded-lg bg-white/95 px-3 py-1.5 text-right shadow-sm backdrop-blur">
                <div class="text-sm font-bold text-sea-900">{{ money($yacht->price_from, $yacht->currency) }}</div>
                <div class="text-[10px] uppercase tracking-wide text-sea-500">{{ __($unitKey) }}</div>
            </div>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-4">
        <h3 class="font-serif text-lg font-semibold leading-tight transition group-hover:text-brass-700">
            {{ $yacht->getTranslation('name', $locale) }}
        </h3>

        <p class="mt-1 text-sm text-sea-500">
            <i class="bi bi-compass"></i>
            {{ yacht_type_label($yacht->type) }}
        </p>

        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1.5 border-t border-sea-100 pt-3 text-xs text-sea-600">
            @if ($yacht->capacity)
                <span><i class="bi bi-people mr-1 text-sea-400"></i>{{ __('site.card.guests', ['count' => $yacht->capacity]) }}</span>
            @endif
            @if ($yacht->cabins)
                <span><i class="bi bi-door-closed mr-1 text-sea-400"></i>{{ __('site.card.cabins', ['count' => $yacht->cabins]) }}</span>
            @endif
            @if ($yacht->length_m)
                <span><i class="bi bi-rulers mr-1 text-sea-400"></i>{{ rtrim(rtrim(number_format((float) $yacht->length_m, 1, ',', '.'), '0'), ',') }} m</span>
            @endif
            <span><i class="bi bi-person-badge mr-1 text-sea-400"></i>{{ $yacht->with_crew ? __('site.list.with_crew') : __('site.list.without_crew') }}</span>
        </div>
    </div>
</a>

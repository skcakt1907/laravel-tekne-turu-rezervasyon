@php
    $action = $action ?? lroute('tours.index');
    $variant = $variant ?? 'floating'; // floating: hero altında kart · inline: sayfa içi
@endphp

<form action="{{ $action }}" method="GET"
      class="{{ $variant === 'floating'
        ? 'rounded-2xl border border-white/15 bg-white/95 p-3 shadow-2xl shadow-sea-950/30 backdrop-blur sm:p-4'
        : 'card p-3 sm:p-4' }}">

    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_0.7fr_auto]">
        <div>
            <label for="s-start" class="label">{{ __('site.search.start') }}</label>
            <input type="date" name="start" id="s-start" class="field"
                   value="{{ request('start') }}" min="{{ now()->toDateString() }}">
        </div>

        <div>
            <label for="s-end" class="label">{{ __('site.search.end') }}</label>
            <input type="date" name="end" id="s-end" class="field"
                   value="{{ request('end') }}" min="{{ now()->addDay()->toDateString() }}">
        </div>

        <div>
            <label for="s-guests" class="label">{{ __('site.search.guests') }}</label>
            <input type="number" name="guests" id="s-guests" class="field" min="1" max="100"
                   value="{{ request('guests') }}" placeholder="2">
        </div>

        <div class="flex items-end">
            <button type="submit" class="btn btn-brass h-[42px] w-full lg:w-auto lg:px-6">
                <i class="bi bi-search"></i>
                <span class="lg:sr-only xl:not-sr-only">{{ __('site.search.submit') }}</span>
            </button>
        </div>
    </div>
</form>

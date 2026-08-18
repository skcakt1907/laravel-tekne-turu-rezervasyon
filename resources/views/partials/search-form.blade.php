@php
    $locale = app()->getLocale();
    $action = $action ?? lroute('yachts.index');
@endphp

<form action="{{ $action }}" method="GET" class="search-box">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-4 col-lg-3">
            <label for="s-port">{{ __('site.search.port') }}</label>
            <select name="port" id="s-port" class="form-select">
                <option value="">{{ __('site.search.all_ports') }}</option>
                @foreach ($ports as $port)
                    <option value="{{ $port->id }}" @selected(request('port') == $port->id)>
                        {{ $port->getTranslation('name', $locale) }}
                        @isset($port->yachts_count) ({{ $port->yachts_count }}) @endisset
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-6 col-md-3 col-lg-2">
            <label for="s-start">{{ __('site.search.start') }}</label>
            <input type="date" name="start" id="s-start" class="form-control"
                   value="{{ request('start') }}" min="{{ now()->toDateString() }}">
        </div>

        <div class="col-6 col-md-3 col-lg-2">
            <label for="s-end">{{ __('site.search.end') }}</label>
            <input type="date" name="end" id="s-end" class="form-control"
                   value="{{ request('end') }}" min="{{ now()->addDay()->toDateString() }}">
        </div>

        <div class="col-6 col-md-2 col-lg-2">
            <label for="s-guests">{{ __('site.search.guests') }}</label>
            <input type="number" name="guests" id="s-guests" class="form-control"
                   min="1" max="100" value="{{ request('guests') }}" placeholder="2">
        </div>

        <div class="col-6 col-md-12 col-lg-3">
            <button type="submit" class="btn btn-brass w-100">
                <i class="bi bi-search me-1"></i>{{ __('site.search.submit') }}
            </button>
        </div>
    </div>
</form>

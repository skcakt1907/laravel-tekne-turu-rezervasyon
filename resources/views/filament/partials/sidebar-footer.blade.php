@php
    $panel = \Filament\Facades\Filament::getCurrentPanel();
@endphp

<div class="mt-2 space-y-0.5 border-t border-gray-200 pt-3 dark:border-white/10">
    <a href="{{ url('/') }}" target="_blank" rel="noopener"
       class="flex items-center gap-2.5 rounded-md px-2.5 py-[7px] text-[13px] text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white">
        <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-4 w-4" />
        Siteyi gor
    </a>

    <form method="POST" action="{{ $panel?->getLogoutUrl() }}">
        @csrf
        <button type="submit"
                class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-[7px] text-[13px] text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white">
            <x-filament::icon icon="heroicon-o-arrow-left-on-rectangle" class="h-4 w-4" />
            Cikis
        </button>
    </form>
</div>

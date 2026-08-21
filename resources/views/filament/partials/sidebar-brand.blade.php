@php
    $panel = \Filament\Facades\Filament::getCurrentPanel();
    $siteName = setting('site_name', config('app.name'));
    $panelLabel = $panel?->getId() === 'owner' ? 'Yat Sahibi Paneli' : 'Yonetim';
@endphp

{{-- Referanstaki "workspace switcher" blogunun karsiligi: kare rozet + ad + alt satir --}}
<div class="mb-3 flex items-center gap-3 rounded-lg px-2 py-2">
    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary-600 text-[13px] font-semibold text-white shadow-sm">
        {{ mb_substr($siteName, 0, 1) }}
    </div>
    <div class="min-w-0 leading-none">
        <div class="truncate text-[13px] font-medium text-gray-900 dark:text-white">{{ $siteName }}</div>
        <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ $panelLabel }}</div>
    </div>
</div>

<x-filament-panels::page>

    @php
        $totals = $this->totals();
        $upcoming = $this->upcoming();
        $months = $this->months();
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold">{{ $this->year }} yılı</h2>
        <div class="flex gap-2">
            <x-filament::button wire:click="previousYear" color="gray" size="sm" icon="heroicon-o-chevron-left">
                {{ $this->year - 1 }}
            </x-filament::button>
            <x-filament::button wire:click="nextYear" color="gray" size="sm"
                                :disabled="$this->year >= now()->year">
                {{ $this->year + 1 }}
            </x-filament::button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Tamamlanan rezervasyon', $totals['count'], null],
            ['Toplam ciro', money($totals['revenue'], $totals['currency']), null],
            ['Toplam komisyon', money($totals['commission'], $totals['currency']), 'Platforma ödenecek'],
            ['Yaklaşan rezervasyon', $upcoming['count'], money($upcoming['revenue'], $upcoming['currency']).' beklenen'],
        ] as [$label, $value, $hint])
            <x-filament::section>
                <div class="text-xs uppercase tracking-wide text-gray-400">{{ $label }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $value }}</div>
                @if ($hint)
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</div>
                @endif
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section>
        <x-slot name="heading">Aylık döküm</x-slot>

        @if (empty($months))
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $this->year }} yılında tamamlanmış rezervasyon yok. Gidiş tarihi geçen onaylı
                rezervasyonlar otomatik olarak tamamlanır ve buraya düşer.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-white/10">
                            <th class="py-2 pr-4">Dönem</th>
                            <th class="py-2 pr-4">Rezervasyon</th>
                            <th class="py-2 pr-4 text-right">Ciro</th>
                            <th class="py-2 pr-4 text-right">Komisyon</th>
                            <th class="py-2 pr-4 text-right">Net</th>
                            <th class="py-2">Tahsilat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($months as $month)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-4 font-medium">{{ $month['label'] }}</td>
                                <td class="py-2 pr-4">{{ $month['count'] }}</td>
                                <td class="py-2 pr-4 text-right">{{ money($month['revenue'], $month['currency']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ money($month['commission'], $month['currency']) }}</td>
                                <td class="py-2 pr-4 text-right font-semibold">
                                    {{ money($month['revenue'] - $month['commission'], $month['currency']) }}
                                </td>
                                <td class="py-2">
                                    @if ($month['collection']?->status === 'collected')
                                        <x-filament::badge color="success">
                                            Tahsil edildi{{ $month['collection']->collected_at ? ' · '.$month['collection']->collected_at->format('d.m.Y') : '' }}
                                        </x-filament::badge>
                                    @else
                                        <x-filament::badge color="warning">Bekliyor</x-filament::badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Tutarlar rezervasyon <strong>onaylandığı andaki</strong> komisyon oranıyla hesaplanır;
            oran sonradan değişse bile geçmiş kayıtlar etkilenmez. Ödeme müşteriyle sizin aranızda
            yapılır, platform yalnızca komisyonunu faturalar.
        </p>
    </x-filament::section>

</x-filament-panels::page>

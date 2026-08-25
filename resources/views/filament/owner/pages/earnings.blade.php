<x-filament-panels::page>

    @php
        $totals = $this->totals();
        $upcoming = $this->upcoming();
        $months = $this->months();
        $chart = $this->monthlyChart();
        $chartMax = max([1, ...$chart]);
        $growth = $this->growth();
        $net = $totals['revenue'] - $totals['commission'];
        $avg = $totals['count'] > 0 ? $totals['revenue'] / $totals['count'] : 0;
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

    {{-- Hero: net hakedis + gecen yila gore buyume --}}
    <div class="relative overflow-hidden rounded-2xl p-8 text-white shadow-xl"
         style="background: linear-gradient(135deg, #0f1417 0%, #262d32 55%, #543a16 100%);">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full"
             style="background: radial-gradient(circle, rgba(192,127,49,0.35), transparent 70%);"></div>
        <div class="relative">
            <p class="text-xs uppercase tracking-[0.15em] text-white/60">{{ $this->year }} net hakedişim</p>
            <p class="mt-2 text-5xl font-black leading-none">{{ money($net, $totals['currency']) }}</p>
            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-white/80">
                <span>Ciro {{ money($totals['revenue'], $totals['currency']) }}</span>
                <span>&middot;</span>
                <span>Komisyon {{ money($totals['commission'], $totals['currency']) }}</span>
                @if ($growth !== null)
                    <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold {{ $growth >= 0 ? 'bg-emerald-500/25 text-emerald-300' : 'bg-red-500/25 text-red-300' }}">
                        <i class="bi {{ $growth >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                        {{ $growth >= 0 ? '+' : '' }}{{ $growth }}% geçen yıla göre
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['heroicon-o-check-circle', '#059669, #10b981', 'Tamamlanan rezervasyon', $totals['count'], null],
            ['heroicon-o-banknotes', '#0369a1, #0ea5e9', 'Toplam ciro', money($totals['revenue'], $totals['currency']), null],
            ['heroicon-o-receipt-percent', '#d97706, #f59e0b', 'Toplam komisyon', money($totals['commission'], $totals['currency']), 'Platforma ödenecek'],
            ['heroicon-o-calculator', '#525b62, #8b949b', 'Ortalama rezervasyon', money($avg, $totals['currency']), null],
            ['heroicon-o-clock', '#7a5314, #c07f31', 'Yaklaşan rezervasyon', $upcoming['count'], money($upcoming['revenue'], $upcoming['currency']).' beklenen'],
        ] as [$icon, $gradient, $label, $value, $hint])
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white"
                          style="background: linear-gradient(135deg, {{ $gradient }});">
                        <x-filament::icon :icon="$icon" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ $label }}</div>
                        <div class="mt-0.5 truncate text-xl font-semibold">{{ $value }}</div>
                    </div>
                </div>
                @if ($hint)
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</div>
                @endif
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section>
        <x-slot name="heading">Aylık ciro</x-slot>

        <div class="flex h-40 items-end gap-2">
            @foreach ($chart as $i => $value)
                @php $monthLabel = now()->setDate($this->year, $i + 1, 1)->translatedFormat('M'); @endphp
                <div class="flex flex-1 flex-col items-center gap-1.5">
                    <div class="flex h-32 w-full items-end">
                        <div class="w-full rounded-t-md transition-all"
                             style="height: {{ $value > 0 ? max(6, round($value / $chartMax * 100)) : 0 }}%; background: linear-gradient(180deg, #c07f31, #7a5314);"
                             title="{{ $monthLabel }}: {{ money($value, $totals['currency']) }}"></div>
                    </div>
                    <span class="text-[10px] uppercase text-gray-400">{{ $monthLabel }}</span>
                </div>
            @endforeach
        </div>
    </x-filament::section>

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

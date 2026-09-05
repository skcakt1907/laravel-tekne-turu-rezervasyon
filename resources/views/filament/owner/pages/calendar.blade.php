<x-filament-panels::page>

    @if ($this->yachts()->isEmpty())
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Henüz tur eklemediniz. Takvim, ilan ekledikten sonra burada görünür.
            </p>
        </x-filament::section>
    @else
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="w-full sm:w-72">
                <label for="yachtId" class="mb-1 block text-sm font-medium">Tur</label>
                <select id="yachtId" wire:model.live="yachtId"
                        class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                    @foreach ($this->yachts() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                @if (! $this->isOpen())
                    <x-filament::badge color="warning">Rezervasyona kapalı</x-filament::badge>
                @endif
                <x-filament::button wire:click="previousMonths" color="gray" size="sm" icon="heroicon-o-chevron-left">
                    Önceki
                </x-filament::button>
                <x-filament::button wire:click="nextMonths" color="gray" size="sm" icon="heroicon-o-chevron-right">
                    Sonraki
                </x-filament::button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($this->months() as $month)
                <x-filament::section>
                    <x-slot name="heading">{{ $month['title'] }}</x-slot>

                    <div class="grid grid-cols-7 gap-1 text-center">
                        @foreach (['Pt', 'Sa', 'Ça', 'Pe', 'Cu', 'Ct', 'Pz'] as $dow)
                            <div class="pb-1 text-[11px] font-medium uppercase text-gray-400">{{ $dow }}</div>
                        @endforeach

                        @foreach ($month['days'] as $day)
                            @if ($day['empty'])
                                <div></div>
                            @else
                                @php
                                    $classes = match (true) {
                                        $day['block'] && $day['block']['reason'] === 'reservation'
                                            => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400 font-medium',
                                        (bool) $day['block']
                                            => 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400',
                                        $day['past'] => 'text-gray-300 dark:text-gray-600',
                                        default => 'bg-gray-50 dark:bg-white/5',
                                    };
                                @endphp
                                <div class="aspect-square rounded-md grid place-items-center text-xs {{ $classes }}"
                                     title="{{ $day['date'] }}{{ $day['block'] ? ' — '.$day['block']['label'] : '' }}">
                                    {{ $day['day'] }}
                                </div>
                            @endif
                        @endforeach
                    </div>
                </x-filament::section>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400">
            <span class="flex items-center gap-2">
                <span class="h-3 w-3 rounded bg-success-100 dark:bg-success-500/20"></span>Onaylı rezervasyon
            </span>
            <span class="flex items-center gap-2">
                <span class="h-3 w-3 rounded bg-warning-100 dark:bg-warning-500/20"></span>Elle kapatılan
            </span>
            <span class="flex items-center gap-2">
                <span class="h-3 w-3 rounded bg-gray-50 dark:bg-white/5"></span>Müsait
            </span>
        </div>

        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Bakım veya özel kullanım için tarih kapatmak isterseniz
                <a href="{{ \App\Filament\Owner\Resources\BlockedPeriods\BlockedPeriodResource::getUrl() }}"
                   class="font-medium text-primary-600 hover:underline dark:text-primary-400">Kapalı Tarihler</a>
                ekranını kullanın. Onaylı rezervasyonlardan gelen bloklar buradan silinemez —
                bunun için rezervasyonu iptal etmeniz gerekir.
            </p>
        </x-filament::section>
    @endif

</x-filament-panels::page>

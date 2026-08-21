<?php

namespace App\Filament\Owner\Pages;

use App\Models\Yacht;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/**
 * Yat sahibinin takvimi: onayli rezervasyonlar + elle bloklar tek gorunumde.
 * Blok ekleme/silme "Kapali Tarihler" kaynagindan yapilir.
 */
class Calendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Takvim';

    protected static ?string $title = 'Takvim';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.owner.pages.calendar';

    public ?int $yachtId = null;

    public int $monthOffset = 0;

    public function mount(): void
    {
        $this->yachtId = $this->yachts()->keys()->first();
    }

    public function previousMonths(): void
    {
        $this->monthOffset = max(0, $this->monthOffset - 3);
    }

    public function nextMonths(): void
    {
        $this->monthOffset += 3;
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    public function yachts()
    {
        return Yacht::where('owner_id', auth()->id())
            ->get()
            ->mapWithKeys(fn (Yacht $y) => [$y->id => $y->getTranslation('name', 'tr')]);
    }

    /**
     * Secili yat icin 3 aylik gun haritasi.
     *
     * @return array<int, array{title: string, days: array<int, array>}>
     */
    public function months(): array
    {
        $yacht = Yacht::where('owner_id', auth()->id())->find($this->yachtId);

        if (! $yacht) {
            return [];
        }

        $from = now()->startOfMonth()->addMonths($this->monthOffset);
        $to = $from->copy()->addMonths(3)->endOfMonth();

        $blocks = $yacht->blockedPeriods()
            ->with('reservation')
            ->overlapping($from, $to)
            ->get();

        $map = [];

        foreach ($blocks as $block) {
            $cursor = $block->starts_at->copy()->startOfDay();

            while ($cursor->lt($block->ends_at)) {
                $map[$cursor->toDateString()] = [
                    'reason' => $block->reason,
                    'label' => $block->reason === 'reservation'
                        ? ($block->reservation?->code ?? 'Rezervasyon')
                        : ($block->note ?: ($block->reason === 'maintenance' ? 'Bakim' : 'Ozel kullanim')),
                ];
                $cursor->addDay();
            }
        }

        $months = [];

        for ($m = 0; $m < 3; $m++) {
            $month = $from->copy()->addMonths($m);
            $days = [];

            for ($i = 0; $i < $month->copy()->startOfMonth()->dayOfWeekIso - 1; $i++) {
                $days[] = ['empty' => true];
            }

            for ($d = 1; $d <= $month->daysInMonth; $d++) {
                $date = $month->copy()->startOfMonth()->addDays($d - 1);
                $key = $date->toDateString();

                $days[] = [
                    'empty' => false,
                    'day' => $d,
                    'past' => $date->isBefore(now()->startOfDay()),
                    'block' => $map[$key] ?? null,
                    'date' => $date->format('d.m.Y'),
                ];
            }

            $months[] = [
                'title' => $month->translatedFormat('F Y'),
                'days' => $days,
            ];
        }

        return $months;
    }

    public function isOpen(): bool
    {
        $yacht = Yacht::where('owner_id', auth()->id())->find($this->yachtId);

        return (bool) $yacht?->is_open;
    }
}

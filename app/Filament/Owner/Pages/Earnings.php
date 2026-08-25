<?php

namespace App\Filament\Owner\Pages;

use App\Enums\ReservationStatus;
use App\Models\Collection as CollectionModel;
use App\Models\Reservation;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Hakedis dokumu. Para siteden gecmedigi icin buradaki is alacagi dogru
 * hesaplamak: aylik ciro, komisyon ve tahsilat durumu.
 *
 * Komisyon tutari rezervasyon ONAYLANDIGINDA dondurulmustu; burada yeniden
 * hesaplanmaz, kayittaki deger toplanir.
 */
class Earnings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Hakedisim';

    protected static ?string $title = 'Hakedisim';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.owner.pages.earnings';

    public int $year;

    public function mount(): void
    {
        $this->year = (int) now()->year;
    }

    public function previousYear(): void
    {
        $this->year--;
    }

    public function nextYear(): void
    {
        $this->year = min((int) now()->year, $this->year + 1);
    }

    /**
     * Aylik dokum. Yalnizca TAMAMLANAN rezervasyonlar komisyona girer
     * (iptal ve gerceklesmeyenler dokume dusmez).
     *
     * @return array<int, array<string, mixed>>
     */
    public function months(): array
    {
        $rows = Reservation::query()
            ->where('owner_id', auth()->id())
            ->where('status', ReservationStatus::Completed)
            ->whereYear('starts_at', $this->year)
            ->get()
            ->groupBy(fn (Reservation $r) => (int) $r->starts_at->format('n'));

        $collections = CollectionModel::where('owner_id', auth()->id())
            ->where('year', $this->year)
            ->get()
            ->keyBy('month');

        $months = [];

        for ($m = 1; $m <= 12; $m++) {
            $group = $rows->get($m);

            if (! $group || $group->isEmpty()) {
                continue;
            }

            $currency = $group->first()->currency;

            $months[] = [
                'month' => $m,
                'label' => now()->setDate($this->year, $m, 1)->translatedFormat('F Y'),
                'count' => $group->count(),
                'revenue' => (float) $group->sum('estimated_total'),
                'commission' => (float) $group->sum('commission_amount'),
                'currency' => $currency,
                'collection' => $collections->get($m),
            ];
        }

        return $months;
    }

    public function totals(): array
    {
        $months = $this->months();

        return [
            'revenue' => array_sum(array_column($months, 'revenue')),
            'commission' => array_sum(array_column($months, 'commission')),
            'count' => array_sum(array_column($months, 'count')),
            'currency' => $months[0]['currency'] ?? config('yacht.default_commission_currency', 'EUR'),
        ];
    }

    /** Önceki yılın toplam cirosu — büyüme yüzdesi için. */
    public function previousYearRevenue(): float
    {
        return (float) Reservation::query()
            ->where('owner_id', auth()->id())
            ->where('status', ReservationStatus::Completed)
            ->whereYear('starts_at', $this->year - 1)
            ->sum('estimated_total');
    }

    /** Bu yılın geçen yıla göre ciro büyümesi (yüzde, null = kıyaslanacak veri yok). */
    public function growth(): ?float
    {
        $previous = $this->previousYearRevenue();
        $current = $this->totals()['revenue'];

        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * 12 aylık ciro dizisi — bos aylar da 0 olarak yer alir (grafik icin).
     *
     * @return array<int, float>
     */
    public function monthlyChart(): array
    {
        $months = collect($this->months())->keyBy('month');

        return collect(range(1, 12))
            ->map(fn (int $m) => (float) ($months->get($m)['revenue'] ?? 0))
            ->all();
    }

    /** Bu ay bekleyen (henuz tamamlanmamis) onayli rezervasyonlar. */
    public function upcoming(): array
    {
        $upcoming = Reservation::where('owner_id', auth()->id())
            ->where('status', ReservationStatus::Approved)
            ->where('starts_at', '>=', now())
            ->get();

        return [
            'count' => $upcoming->count(),
            'revenue' => (float) $upcoming->sum('estimated_total'),
            'currency' => $upcoming->first()?->currency ?? 'EUR',
        ];
    }
}

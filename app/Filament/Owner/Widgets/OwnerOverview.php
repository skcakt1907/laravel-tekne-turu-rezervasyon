<?php

namespace App\Filament\Owner\Widgets;

use App\Enums\ReservationStatus;
use App\Enums\YachtStatus;
use App\Models\Reservation;
use App\Models\Yacht;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OwnerOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $ownerId = auth()->id();

        $pending = Reservation::where('owner_id', $ownerId)
            ->where('status', ReservationStatus::Pending)
            ->count();

        $thisMonth = Reservation::where('owner_id', $ownerId)
            ->whereIn('status', [ReservationStatus::Approved, ReservationStatus::Completed])
            ->whereYear('starts_at', now()->year)
            ->whereMonth('starts_at', now()->month);

        $published = Yacht::where('owner_id', $ownerId)->where('status', YachtStatus::Published)->count();
        $draft = Yacht::where('owner_id', $ownerId)->whereIn('status', [
            YachtStatus::Draft, YachtStatus::Pending, YachtStatus::Rejected,
        ])->count();

        $currency = Yacht::where('owner_id', $ownerId)->value('currency') ?? 'EUR';

        return [
            Stat::make('Yanit bekleyen talep', $pending)
                ->description($pending ? 'Hizli donus rezervasyona donusur' : 'Bekleyen talep yok')
                ->color($pending ? 'warning' : 'success'),

            Stat::make('Bu ayki rezervasyon', $thisMonth->clone()->count())
                ->description('Onayli ve tamamlanan')
                ->color('info'),

            Stat::make('Bu ayki ciro', money((float) $thisMonth->clone()->sum('estimated_total'), $currency))
                ->description('Komisyon: '.money((float) $thisMonth->clone()->sum('commission_amount'), $currency))
                ->color('success'),

            Stat::make('Yayindaki ilan', $published)
                ->description($draft ? "{$draft} ilan taslak/onay bekliyor" : 'Tum ilanlar yayinda')
                ->color($draft ? 'warning' : 'success'),
        ];
    }
}

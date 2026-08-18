<?php

namespace App\Filament\Widgets;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Enums\YachtStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Yacht;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $pendingReservations = Reservation::where('status', ReservationStatus::Pending)->count();
        $escalated = Reservation::where('status', ReservationStatus::Pending)
            ->whereNotNull('escalated_at')
            ->count();
        $pendingYachts = Yacht::where('status', YachtStatus::Pending)->count();
        $pendingOwners = User::where('role', UserRole::Owner)->where('is_approved', false)->count();

        $monthly = Reservation::whereIn('status', [ReservationStatus::Approved, ReservationStatus::Completed])
            ->whereYear('starts_at', now()->year)
            ->whereMonth('starts_at', now()->month);

        return [
            Stat::make('Bekleyen talep', $pendingReservations)
                ->description($escalated ? "{$escalated} tanesi müdahale bekliyor" : 'Yanıt bekleyen rezervasyon talebi')
                ->color($escalated ? 'danger' : ($pendingReservations ? 'warning' : 'success')),

            Stat::make('Onay bekleyen ilan', $pendingYachts)
                ->description('Yayına alınmayı bekliyor')
                ->color($pendingYachts ? 'warning' : 'success'),

            Stat::make('Onay bekleyen yat sahibi', $pendingOwners)
                ->description('Panele erişimi açılmadı')
                ->color($pendingOwners ? 'warning' : 'success'),

            Stat::make('Bu ay ciro', number_format((float) $monthly->clone()->sum('estimated_total'), 0, ',', '.'))
                ->description('Komisyon: '.number_format((float) $monthly->clone()->sum('commission_amount'), 0, ',', '.'))
                ->color('info'),
        ];
    }
}

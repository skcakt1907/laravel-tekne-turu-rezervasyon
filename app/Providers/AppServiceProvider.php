<?php

namespace App\Providers;

use App\Models\Location;
use App\Models\Page;
use App\Models\Yacht;
use App\Models\YachtRate;
use App\Observers\YachtObserver;
use App\Observers\YachtRateObserver;
use App\Listeners\SendReservationNotifications;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Yacht::observe(YachtObserver::class);
        YachtRate::observe(YachtRateObserver::class);

        Event::subscribe(SendReservationNotifications::class);

        Paginator::useBootstrapFive();

        // Site onyuzunun her sayfasinda lazim olan veriler
        View::composer('layouts.site', function ($view) {
            $view->with([
                'navPorts' => Location::query()
                    ->where('level', Location::LEVEL_PORT)
                    ->where('is_active', true)
                    ->where('is_featured', true)
                    ->orderBy('sort')
                    ->get(),
                'footerPages' => Page::query()
                    ->where('is_active', true)
                    ->orderBy('sort')
                    ->get(),
            ]);
        });
    }
}

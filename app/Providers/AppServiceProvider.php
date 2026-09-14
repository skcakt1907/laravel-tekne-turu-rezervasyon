<?php

namespace App\Providers;

use App\Listeners\SendReservationNotifications;
use App\Models\Page;
use App\Models\YachtRate;
use App\Observers\YachtRateObserver;
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
        YachtRate::observe(YachtRateObserver::class);

        Event::subscribe(SendReservationNotifications::class);

        Paginator::useBootstrapFive();

        // Site onyuzunun her sayfasinda lazim olan veriler
        View::composer('layouts.site', function ($view) {
            $view->with([
                'footerPages' => Page::query()
                    ->where('is_active', true)
                    ->orderBy('sort')
                    ->get(),
            ]);
        });
    }
}

<?php

namespace App\Providers;

use App\Listeners\SendReservationNotifications;
use App\Models\Page;
use App\Models\YachtRate;
use App\Observers\YachtRateObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
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

        /*
         * Şifre sıfırlama maili: Laravel'in İngilizce varsayılanı yerine kendi
         * metnimiz ve SİTE adresimiz. Varsayılan bırakılırsa müşteriye İngilizce
         * mail gider ve bağlantı yanlış rotayı gösterir.
         */
        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', ['token' => $token], false))
                .'?email='.urlencode($notifiable->getEmailForPasswordReset());

            return (new MailMessage)
                ->subject(__('mail.password.subject', ['site' => setting('site_name', config('app.name'))]))
                ->greeting(__('mail.common.hello', ['name' => $notifiable->name]))
                ->line(__('mail.password.intro'))
                ->action(__('mail.password.action'), $url)
                ->line(__('mail.password.expire', ['count' => config('auth.passwords.users.expire', 60)]))
                ->line(__('mail.password.ignore'))
                ->salutation(__('mail.common.regards'));
        });

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

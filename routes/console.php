<?php

use Illuminate\Support\Facades\Schedule;

/*
| Rezervasyon otomasyonu saat basi calisir:
| - 4 saat yanitsiz talep -> yat sahibine hatirlatma
| - 12 saat yanitsiz talep -> admin devralir
| - Gidis tarihi gecen onayli rezervasyon -> tamamlandi
| - Kalkisa 3 gun kala -> musteriye hatirlatma
|
| Canlida cron: * * * * * php /path/artisan schedule:run >> /dev/null 2>&1
*/
Schedule::command('reservations:process')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

/*
| Aylik hakedis dokumu: her ayin 1'inde, kapanmis onceki ay icin uretilir.
| Tahsil edilmis donemler yeniden hesaplanmaz.
*/
Schedule::command('collections:build')
    ->monthlyOn(1, '03:00')
    ->withoutOverlapping();

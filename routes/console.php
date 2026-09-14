<?php

use Illuminate\Support\Facades\Schedule;

/*
| Rezervasyon otomasyonu saat basi calisir:
| - 4 saat yanitsiz rezervasyon -> hatirlatma
| - 12 saat yanitsiz rezervasyon -> yonetim ekraninda isaretlenir
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
| Aylik hakedis dokumu kaldirildi: komisyon/tahsilat pazar yeri modelinin
| parcasiydi, tek firmada karsiligi yok.
*/

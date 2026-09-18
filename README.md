# Tekne Turu Rezervasyon Sistemi

Gunluk tekne turlari icin talep bazli rezervasyon sitesi; yonetim paneli Filament ile, onay akisi WhatsApp uzerinden.

## Ozellikler

- Tur katalogu, kapasite ve musaitlik takibi
- Odemesiz, talep bazli rezervasyon akisi (talep -> onay/ret -> kesinlesme)
- WhatsApp Cloud API entegrasyonu: sablonlu bildirim ve butonla onay/ret
- Webhook ile gelen buton yanitlarinin islenmesi
- Filament tabanli yonetim paneli, cok dilli on yuz, sitemap

## Kullanilan teknolojiler

Laravel 13 - PHP 8.3 - Filament - Tailwind - WhatsApp Cloud API

## Bu depo hakkinda

Gercek bir musteri projesinin **portfolyo icin yayinlanmis** surumudur.
Yayina hazirlanirken canli alan adlari, gercek iletisim bilgileri, musteri
kayitlari ve uygulama anahtarlari ornek degerlerle degistirilmistir.
Kod ve mimari oldugu gibidir; veri gercek degildir.

## Kurulum

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

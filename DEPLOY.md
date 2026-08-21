# Yayın Rehberi

Paylaşımlı hosting veya VPS fark etmez; kritik nokta **docroot'un `public/` klasörü
olması**. Build adımı yoktur (Bootstrap CDN'den gelir, Filament varlıkları
`public/` içinde hazır bekler).

## 1. Sunucu gereksinimleri

- PHP **8.3+** — `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `zip`, `intl`
- MySQL 8 / MariaDB 10.6+
- HTTPS (Let's Encrypt yeterli) — WhatsApp webhook'u HTTPS zorunlu kılar
- Cron çalıştırma yetkisi

## 2. Dosyaları yükleme

```bash
git clone <repo> /var/www/yat-kiralama
cd /var/www/yat-kiralama
composer install --no-dev --optimize-autoloader
```

> **Tuzak:** `--no-dev` sonrası `bootstrap/cache/` içinde eski paket listesi kalırsa
> "Class not found" hatası alırsınız. Şüphelenirseniz `bootstrap/cache/*.php`
> dosyalarını silip `php artisan optimize` çalıştırın.

Alan adının docroot'unu `/var/www/yat-kiralama/public` yapın. Docroot'u
değiştiremiyorsanız (bazı paylaşımlı hostingler) `public/` içeriğini kök dizine
taşıyıp `index.php` içindeki iki `require` yolunu bir üst klasöre çevirin.

## 3. Ortam dosyası

```bash
cp .env.example .env
php artisan key:generate
```

Doldurulacaklar:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://alan-adi

DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=...

MAIL_MAILER=smtp
MAIL_HOST=... MAIL_PORT=587 MAIL_USERNAME=... MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=info@alan-adi
MAIL_FROM_NAME="${APP_NAME}"

# WhatsApp: Meta onayı tamamlanınca (bkz. WHATSAPP.md)
WHATSAPP_ENABLED=false
```

`APP_DEBUG=false` **şart** — açık kalırsa hata sayfaları veritabanı bilgilerini
gösterir.

## 4. Veritabanı ve dosya izinleri

```bash
php artisan migrate --force
php artisan db:seed --force        # yalnızca ilk kurulumda
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

Seed sonrası **admin şifresini hemen değiştirin** (`/yonetim` → Kullanıcılar).
Demo yat sahibi ve demo ilanları da silin.

## 5. Cron

```
* * * * * cd /var/www/yat-kiralama && php artisan schedule:run >> /dev/null 2>&1
```

Tek satır yeter; içindeki iki iş buradan yürür:

| İş | Sıklık | Ne yapar |
|---|---|---|
| `reservations:process` | saat başı | 4 saat hatırlatma, 12 saat admin devri, otomatik tamamlama, gidiş hatırlatması |
| `collections:build` | ayın 1'i 03:00 | önceki ayın hakediş dökümü |

Cron kurulmazsa sistem çalışır ama **hatırlatmalar ve hakediş dökümü hiç
üretilmez** — sessiz kayıp olur.

## 6. Üretim önbelleği

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Kod veya `.env` değişince bu dördünü tekrar çalıştırın (`php artisan optimize`).

## 7. Yayın öncesi kontrol listesi

- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Admin şifresi değişti, demo hesaplar/ilanlar silindi
- [ ] SMTP çalışıyor (bir test rezervasyonu gönderip maili doğrulayın)
- [ ] Cron kurulu ve çalışıyor (`php artisan schedule:list` ile doğrula)
- [ ] Site adı, telefon, e-posta, adres `/yonetim/site-settings` içinde dolu
- [ ] Komisyon oranı `/yonetim/commission-settings` içinde ayarlandı
- [ ] Yasal metinler (KVKK, kullanım koşulları, iptal politikası) **avukat onayından geçti**
  ve `{{FİRMA UNVANI}}` yer tutucuları dolduruldu
- [ ] `sitemap.xml` açılıyor, `robots.txt` doğru alan adını gösteriyor
- [ ] Google Search Console'a site haritası bildirildi
- [ ] Analytics ölçüm kimliği girildi (isteğe bağlı)
- [ ] Yedekleme kuruldu (veritabanı + `public/storage` görselleri)
- [ ] SSL zorunlu (http → https yönlendirmesi)

## 8. Yayın sonrası

- **WhatsApp**: `WHATSAPP.md` — Meta doğrulaması, şablon onayı, webhook, Chatwoot.
- **İçerik**: gerçek yat görselleri (kapak 16:9, en az 1600px genişlik önerilir),
  liman sayfalarına özgün SEO metni.
- **İzleme**: `/yonetim/message-logs` ekranından bildirim teslim durumlarını takip edin;
  `storage/logs/laravel.log` dosyasını dönemsel temizleyin.

## 9. Geri alma

Sürüm etiketleyerek ilerleyin; sorun çıkarsa:

```bash
git checkout <önceki-etiket>
composer install --no-dev --optimize-autoloader
php artisan migrate:rollback --step=1   # yalnızca gerekiyorsa
php artisan optimize
```

Migration geri alma veri kaybı yaratabilir — önce yedek alın.

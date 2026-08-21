# WhatsApp Cloud API — Kurulum ve İşletme Notları

Kod tarafı hazır ve testli. Bu belge **kod dışında** yapılması gerekenleri anlatır.
Kanal `WHATSAPP_ENABLED=false` iken tüm bildirimler yalnızca e-postadan gider;
site bu hâliyle de yayına girebilir.

## 1. Kurulum zinciri

| # | Adım | Süre | Kim |
|---|------|------|-----|
| 1 | Meta Business Manager hesabı + **işletme doğrulaması** | 3 gün – 2 hafta | Müşteri (evrak) + biz |
| 2 | WhatsApp Business hesabı (WABA) açılması | 1 gün | Biz |
| 3 | Numara kaydı ve doğrulaması | 1 saat | Biz |
| 4 | Şablonların onaya gönderilmesi | Birkaç saat – 2 gün | Biz |
| 5 | Webhook bağlantısı + Chatwoot | 1 gün | Biz |

**İşletme doğrulaması için gereken evraklar:** vergi levhası, faaliyet belgesi,
imza sirküleri, işletme adresi ve telefonu.

## 2. Numara kuralı (dikkat)

API'ye kaydedilen numara **normal WhatsApp veya WhatsApp Business uygulamasında
artık kullanılamaz.** Hiç kullanılmamış ya da kaydı silinebilecek temiz bir numara
gerekir; sabit hat da olur. Elle yazışma ihtiyacı Chatwoot'tan karşılanır (bkz. 6).

## 3. `.env` değerleri

```
WHATSAPP_ENABLED=true
WHATSAPP_API_VERSION=v21.0
WHATSAPP_PHONE_NUMBER_ID=       # Meta > WhatsApp > API Setup
WHATSAPP_BUSINESS_ACCOUNT_ID=
WHATSAPP_ACCESS_TOKEN=          # kalıcı (system user) token kullanın
WHATSAPP_VERIFY_TOKEN=          # kendi ürettiğiniz rastgele dize
```

Token'ı **geçici** (24 saat) değil, System User üzerinden üretilen kalıcı token
olarak alın; aksi hâlde ertesi gün tüm gönderimler `failed` düşer.

## 4. Webhook

Meta > WhatsApp > Configuration > Webhook:

- **Callback URL:** `https://alan-adi/webhook/whatsapp`
- **Verify token:** `.env`'deki `WHATSAPP_VERIFY_TOKEN`
- **Abone olunacak alanlar:** `messages` (buton yanıtı + teslim/okundu durumları)

Doğrulama isteği `GET`, olaylar `POST` gelir. Rota CSRF'den muaftır
(`bootstrap/app.php`). Hatalı olayda bile 200 döneriz — aksi hâlde Meta aynı
olayı tekrar tekrar gönderir.

## 5. Şablonlar

8 şablon, TR + EN olmak üzere 16 kayıt. Hepsi **UTILITY** kategorisinde:
en ucuz sınıf ve onaydan geçme ihtimali en yüksek olan.

```bash
php artisan whatsapp:templates                    # tablo hâlinde listele
php artisan whatsapp:templates --out=templates.json   # Meta yükü olarak dışa aktar
```

Şablon metinleri `app/Services/WhatsApp/TemplateRegistry.php` içinde tek yerde
durur. **Meta'daki metni elle değiştirirseniz koddaki parametre sırasıyla
ayrışır** ve mesajlar yanlış bilgiyle gider; değişikliği önce burada yapın.
Bir test (`test_every_template_has_matching_parameter_count`) gövdedeki `{{n}}`
sayısı ile kodun ürettiği parametre sayısını karşılaştırır.

Onay butonu yalnızca yat sahibine giden iki şablonda var (`yeni_talep_sahip`,
`bekleyen_talep_sahip`): **Onayla / Reddet** hızlı yanıt butonları. Yedek yol
olarak güvenli bağlantı da mesaj gövdesinde.

## 6. Chatwoot (elle yazışma)

Açık kaynaklı ve ücretsiz. **Sunucu tarafı bir iştir, uygulama koduyla ilgisi
yoktur** — Docker çalıştırabilen bir sunucu gerekir (Windows/WAMP ortamında
kurulmaz).

Kaba hatlarıyla:

1. Sunucuya Docker + Docker Compose kurulur.
2. Chatwoot resmi compose dosyasıyla ayağa kaldırılır, alt alan adı verilir
   (ör. `destek.alan-adi`), Let's Encrypt ile SSL alınır.
3. Chatwoot içinde **Inbox > WhatsApp > Cloud API** seçilir; aynı
   `phone_number_id` ve access token girilir.
4. Meta'daki webhook adresi Chatwoot'a çevrilir, Chatwoot da olayları bize
   iletir — ya da tersi: bizim uçtan Chatwoot'a kopyalanır.

> **Karar gerektiren nokta:** Meta tek bir webhook adresi kabul eder. İki uç da
> (biz + Chatwoot) olay almak istiyorsa, bizim webhook'umuz olayları Chatwoot'a
> iletmelidir. Bu köprü henüz yazılmadı; Chatwoot kurulduğunda 1 günlük iş.

Personel tarayıcıdan aynı numara üzerinden yazışır, birden fazla kişi aynı anda
çalışabilir, tüm geçmiş kayıt altında kalır.

## 7. Uyulacak kurallar

- **Açık rıza zorunlu.** Rezervasyon formundaki WhatsApp onay kutusu zorunlu;
  onay tarih + IP ile `consents` tablosuna yazılır. Rıza kaydı yoksa müşteriye
  WhatsApp **gönderilmez** (kod bunu kendi kontrol eder).
- **24 saat kuralı.** Müşteri size yazdıktan sonraki 24 saat serbest metin
  gönderilebilir; dışında yine şablon gerekir. Bu yüzden kodda serbest metin
  gönderimi hiç yok.
- **Pazarlama yok.** Kampanya/duyuru bu numaradan gönderilmez. Şikâyet oranı
  yükselirse numara kısıtlanır ve tüm bildirim akışı durur.
- **Isınma.** Yeni numaranın günlük limiti düşük başlar, kaliteli kullanımla
  kademeli yükselir. İlk haftalarda e-posta yedek kanal olarak kritik —
  zaten her bildirim iki kanaldan da gider.

## 8. İzleme

`/yonetim/message-logs` — giden her mesaj, kanal, şablon, alıcı, durum
(kuyrukta / gönderildi / teslim / okundu / başarısız) ve hata sebebi.
WhatsApp durumları Meta'dan webhook ile gelir, kayıt kendiliğinden güncellenir.

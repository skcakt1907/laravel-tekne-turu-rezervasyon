<?php

namespace App\Services\WhatsApp;

use App\Models\Reservation;

/**
 * Sistem içi şablon anahtarı ↔ Meta şablonu eşlemesi.
 *
 * Her şablon Utility kategorisinde; metinler kısa ve tamamen bilgilendirici
 * (pazarlama dili numaranın kısıtlanmasına yol açar). Onay butonu yalnızca
 * tur sahibine giden şablonda var; yedek yol olarak güvenli bağlantı gövdede.
 *
 * `php artisan whatsapp:templates` bu tanımları Meta'ya yüklenecek JSON olarak basar.
 */
class TemplateRegistry
{
    /** Hızlı yanıt butonu yükleri — webhook bunlarla eşleştirir. */
    public const BUTTON_APPROVE = 'ONAYLA';

    public const BUTTON_REJECT = 'REDDET';

    /**
     * Şablon gövdeleri. {{n}} sırası paramsFor() ile birebir aynı olmalı.
     *
     * @return array<string, array{name: string, buttons: array<int, string>, body: array<string, string>}>
     */
    public static function definitions(): array
    {
        return [
            'request_received_customer' => [
                'name' => 'talep_alindi_musteri',
                'buttons' => [],
                'body' => [
                    'tr' => "Merhaba {{1}}, {{2}} için {{3}} tarihli rezervasyon talebiniz alındı. Rezervasyon kodunuz: {{4}}. Tur sahibi onayladığında size bilgi vereceğiz. Durumu buradan takip edebilirsiniz: {{5}}.",
                    'en' => "Hello {{1}}, we received your booking request for {{2}} on {{3}}. Your booking code is {{4}}. We will let you know once the tour owner approves. You can track it here: {{5}}.",
                ],
            ],
            'request_new_owner' => [
                'name' => 'yeni_talep_sahip',
                'buttons' => [self::BUTTON_APPROVE, self::BUTTON_REJECT],
                'body' => [
                    'tr' => "{{1}} için yeni rezervasyon talebi: {{2}}, {{3}} kişi, tahmini {{4}}. Talep kodu {{5}}. Aşağıdaki butonlarla yanıtlayabilir veya bağlantıyı kullanabilirsiniz: {{6}}.",
                    'en' => "New booking request for {{1}}: {{2}}, {{3}} guests, estimated {{4}}. Request code {{5}}. Reply with the buttons below or use this link: {{6}}.",
                ],
            ],
            'request_new_admin' => [
                'name' => 'yeni_talep_admin',
                'buttons' => [],
                'body' => [
                    'tr' => "Yeni talep: {{1}} — {{2}} — {{3}}. Kod {{4}}. Yönetim paneli: {{5}}.",
                    'en' => "New request: {{1}} — {{2}} — {{3}}. Code {{4}}. Admin panel: {{5}}.",
                ],
            ],
            'approved_customer' => [
                'name' => 'rezervasyon_onaylandi_musteri',
                'buttons' => [],
                'body' => [
                    'tr' => "Merhaba {{1}}, {{2}} için {{3}} tarihli rezervasyonunuz onaylandı. Kod: {{4}}. Detaylar: {{5}}.",
                    'en' => "Hello {{1}}, your booking for {{2}} on {{3}} has been approved. Code: {{4}}. Details: {{5}}.",
                ],
            ],
            'approved_owner' => [
                'name' => 'rezervasyon_kesinlesti_sahip',
                'buttons' => [],
                'body' => [
                    'tr' => "{{1}} için {{2}} tarihli rezervasyon kesinleşti. Müşteri: {{3}}, telefon {{4}}. Kod {{5}}.",
                    'en' => "The booking for {{1}} on {{2}} is confirmed. Guest: {{3}}, phone {{4}}. Code {{5}}.",
                ],
            ],
            'rejected_customer' => [
                'name' => 'talep_karsilanamadi_musteri',
                'buttons' => [],
                'body' => [
                    'tr' => "Merhaba {{1}}, {{2}} için {{3}} tarihli talebiniz maalesef karşılanamadı. Kod {{4}}. Diğer turlara buradan bakabilirsiniz: {{5}}.",
                    'en' => "Hello {{1}}, unfortunately your request for {{2}} on {{3}} could not be fulfilled. Code {{4}}. You can browse other tours here: {{5}}.",
                ],
            ],
            'pending_reminder_owner' => [
                'name' => 'bekleyen_talep_sahip',
                'buttons' => [self::BUTTON_APPROVE, self::BUTTON_REJECT],
                'body' => [
                    'tr' => "Hatırlatma: {{1}} için {{2}} tarihli talep hâlâ yanıt bekliyor. Kod {{3}}. Yanıtlamak için: {{4}}.",
                    'en' => "Reminder: the request for {{1}} on {{2}} is still awaiting your reply. Code {{3}}. Respond here: {{4}}.",
                ],
            ],
            'trip_reminder_customer' => [
                'name' => 'gidis_hatirlatma_musteri',
                'buttons' => [],
                'body' => [
                    'tr' => "Merhaba {{1}}, {{2}} ile yolculuğunuza 3 gün kaldı. Kalkış {{3}}. Kod {{4}}. Detaylar: {{5}}.",
                    'en' => "Hello {{1}}, only 3 days until your trip with {{2}}. Departure {{3}}. Code {{4}}. Details: {{5}}.",
                ],
            ],
        ];
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, static::definitions());
    }

    public static function metaName(string $key): ?string
    {
        return static::definitions()[$key]['name'] ?? null;
    }

    /** @return array<int, string> */
    public static function buttons(string $key): array
    {
        return static::definitions()[$key]['buttons'] ?? [];
    }

    /**
     * Şablon gövdesindeki {{1}}, {{2}}... değerleri. Sıra gövde metniyle aynı olmalı.
     *
     * @return array<int, string>
     */
    public static function paramsFor(string $key, Reservation $reservation, string $locale): array
    {
        $yacht = $reservation->yacht->getTranslation('name', $locale);
        $dates = $reservation->starts_at->format('d.m.Y').' - '.$reservation->ends_at->format('d.m.Y');
        $total = money($reservation->estimated_total, $reservation->currency);
        $customerUrl = static::url('reservation.show', $locale, ['code' => $reservation->code])
            .'?token='.$reservation->access_token;
        $decisionUrl = static::url('reservation.decision', $locale, [
            'code' => $reservation->code,
            'token' => $reservation->access_token,
        ]);

        return match ($key) {
            'request_received_customer' => [
                $reservation->customer_name, $yacht, $dates, $reservation->code, $customerUrl,
            ],
            'request_new_owner' => [
                $yacht, $dates, (string) $reservation->guests, $total, $reservation->code, $decisionUrl,
            ],
            'request_new_admin' => [
                $yacht, $dates, $reservation->customer_name, $reservation->code, url('/yonetim/reservations'),
            ],
            'approved_customer' => [
                $reservation->customer_name, $yacht, $dates, $reservation->code, $customerUrl,
            ],
            'approved_owner' => [
                $yacht, $dates, $reservation->customer_name, $reservation->customer_phone, $reservation->code,
            ],
            'rejected_customer' => [
                $reservation->customer_name, $yacht, $dates, $reservation->code,
                static::url('tours.index', $locale),
            ],
            'pending_reminder_owner' => [
                $yacht, $dates, $reservation->code, $decisionUrl,
            ],
            'trip_reminder_customer' => [
                $reservation->customer_name, $yacht,
                $reservation->starts_at->format('d.m.Y H:i'), $reservation->code, $customerUrl,
            ],
            default => [],
        };
    }

    /** Alıcının dilindeki adres (uygulama dili alıcıdan bağımsız olabilir). */
    private static function url(string $name, string $locale, array $params = []): string
    {
        $default = array_key_first(config('yacht.locales', ['tr' => []]));
        $resolved = $locale === $default ? $name : "{$locale}.{$name}";

        return \Illuminate\Support\Facades\Route::has($resolved)
            ? route($resolved, $params)
            : route($name, $params);
    }

    /**
     * Meta Business Manager'a yüklenecek şablon yükleri.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function metaPayloads(): array
    {
        $payloads = [];

        foreach (static::definitions() as $definition) {
            foreach ($definition['body'] as $locale => $body) {
                $components = [[
                    'type' => 'BODY',
                    'text' => $body,
                ]];

                if ($definition['buttons']) {
                    $components[] = [
                        'type' => 'BUTTONS',
                        'buttons' => array_map(fn (string $payload) => [
                            'type' => 'QUICK_REPLY',
                            'text' => $payload === self::BUTTON_APPROVE
                                ? ($locale === 'tr' ? 'Onayla' : 'Approve')
                                : ($locale === 'tr' ? 'Reddet' : 'Decline'),
                        ], $definition['buttons']),
                    ];
                }

                $payloads[] = [
                    'name' => $definition['name'],
                    'language' => $locale === 'tr' ? 'tr' : 'en',
                    'category' => 'UTILITY',
                    'components' => $components,
                ];
            }
        }

        return $payloads;
    }
}

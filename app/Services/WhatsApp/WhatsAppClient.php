<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Meta WhatsApp Cloud API istemcisi — arada sağlayıcı yok.
 *
 * Yalnızca Utility kategorisindeki onaylı şablonlar gönderilir; serbest metin
 * gönderimi bilerek yok (24 saat penceresi dışında Meta reddeder, pazarlama
 * mesajı numaranın kısıtlanmasına yol açar).
 */
class WhatsAppClient
{
    public function enabled(): bool
    {
        return (bool) config('whatsapp.enabled')
            && filled(config('whatsapp.phone_number_id'))
            && filled(config('whatsapp.access_token'));
    }

    /**
     * Şablon mesajı gönderir, Meta'nın mesaj kimliğini döndürür.
     *
     * @param  array<int, string>  $bodyParams  Şablon gövdesindeki {{1}}, {{2}}... sırayla
     * @param  array<int, string>  $buttonParams  URL butonu varsa dinamik son parça
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        string $language,
        array $bodyParams = [],
        array $buttonParams = [],
    ): string {
        if (! $this->enabled()) {
            throw new RuntimeException('WhatsApp kapalı: yapılandırma eksik.');
        }

        $components = [];

        if ($bodyParams) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn ($value) => [
                    'type' => 'text',
                    'text' => $this->clean($value),
                ], array_values($bodyParams)),
            ];
        }

        foreach (array_values($buttonParams) as $index => $value) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => (string) $index,
                'parameters' => [['type' => 'text', 'text' => $this->clean($value)]],
            ];
        }

        $response = $this->post('messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizeNumber($to),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'WhatsApp gönderimi başarısız: '.($response->json('error.message') ?? $response->body())
            );
        }

        return (string) $response->json('messages.0.id');
    }

    /** Okundu bilgisi için gelen mesajı işaretler (kullanıcı bize yazdığında). */
    public function markAsRead(string $messageId): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->post('messages', [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ]);
    }

    /**
     * Numara E.164 biçimine indirgenir: baştaki + ve aradaki boşluk/tire atılır.
     * Türkiye numaralarında 0'la başlayan yerel yazım 90 ön ekine çevrilir.
     */
    public function normalizeNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = '90'.substr($digits, 1);
        }

        return $digits;
    }

    private function post(string $endpoint, array $payload): Response
    {
        $version = config('whatsapp.api_version', 'v21.0');
        $phoneId = config('whatsapp.phone_number_id');

        return Http::withToken(config('whatsapp.access_token'))
            ->acceptJson()
            ->timeout(15)
            ->post("https://graph.facebook.com/{$version}/{$phoneId}/{$endpoint}", $payload);
    }

    /** Şablon parametrelerinde satır sonu ve ardışık boşluk Meta tarafından reddedilir. */
    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }
}

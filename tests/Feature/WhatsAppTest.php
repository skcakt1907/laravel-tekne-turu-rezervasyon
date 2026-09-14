<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Consent;
use App\Models\MessageLog;
use App\Models\Reservation;
use App\Models\Yacht;
use App\Services\WhatsApp\TemplateRegistry;
use App\Services\WhatsApp\WhatsAppClient;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\FormKorumasiVerisi;
use Tests\TestCase;

class WhatsAppTest extends TestCase
{
    use FormKorumasiVerisi;
    use RefreshDatabase;

    private Yacht $yacht;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->yacht = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();

        Mail::fake();
    }

    private function enableWhatsApp(): void
    {
        config([
            'whatsapp.enabled' => true,
            'whatsapp.phone_number_id' => '123456',
            'whatsapp.access_token' => 'test-token',
            'whatsapp.verify_token' => 'dogrulama',
            'whatsapp.app_secret' => 'test-app-secret',
        ]);
    }

    /** Meta'nin gonderdigi X-Hub-Signature-256 imzasiyla webhook'a POST atar. */
    private function postSignedWebhook(array $payload)
    {
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, config('whatsapp.app_secret'));

        return $this->call('POST', '/webhook/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Hub-Signature-256' => $signature,
        ], $body);
    }

    public function test_nothing_is_sent_while_the_channel_is_off(): void
    {
        config(['whatsapp.enabled' => false]);
        Http::fake();

        $this->makeReservation();

        Http::assertNothingSent();
        $this->assertSame(0, MessageLog::where('channel', 'whatsapp')->count());
    }

    public function test_request_sends_templates_to_customer_and_admin(): void
    {
        $this->enableWhatsApp();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TEST']]])]);

        $reservation = $this->makeReservation();

        $logs = MessageLog::where('channel', 'whatsapp')->where('related_id', $reservation->id)->get();

        // Tur sahibi diye ayri bir taraf kalmadi: musteri + yonetim
        $this->assertCount(2, $logs);
        $this->assertTrue($logs->every(fn (MessageLog $l) => $l->status === 'sent' && $l->provider_message_id === 'wamid.TEST'));

        // Meta'ya giden yuk: onayli sablon adi + dogru dil
        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['type'] === 'template'
                && $body['template']['language']['code'] === 'tr'
                && in_array($body['template']['name'], [
                    'talep_alindi_musteri', 'yeni_talep_admin',
                ], true);
        });
    }

    public function test_customer_without_consent_gets_no_whatsapp(): void
    {
        $this->enableWhatsApp();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]])]);

        $reservation = $this->makeReservation();

        // Riza kaydini kaldir, yeni bildirim tetikle
        Consent::where('subject_id', $reservation->id)->where('type', Consent::TYPE_WHATSAPP)->delete();
        MessageLog::query()->delete();

        // Onay bildirimi artik yalnizca musteriye gidiyor; "rizasi yoksa
        // musteriye gitmez ama personele gider" kurali, personele de giden
        // bir bildirim uzerinden sinaniyor.
        app(\App\Services\NotificationService::class)->reservationRequested($reservation);

        $recipients = MessageLog::where('channel', 'whatsapp')->pluck('recipient');

        $this->assertNotContains($reservation->customer_phone, $recipients);

        $yonetici = \App\Models\User::where('email', 'admin@yatkiralama.com')->firstOrFail();
        $this->assertContains($yonetici->notificationPhone(), $recipients); // personel rizaya bagli degil
    }

    public function test_send_failure_is_logged_and_does_not_break_the_flow(): void
    {
        $this->enableWhatsApp();
        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['message' => 'Template name does not exist'],
        ], 400)]);

        $reservation = $this->makeReservation();

        $this->assertSame(ReservationStatus::Pending, $reservation->status);

        $failed = MessageLog::where('channel', 'whatsapp')->where('status', 'failed')->get();
        $this->assertGreaterThan(0, $failed->count());
        $this->assertStringContainsString('Template name does not exist', $failed->first()->error);
    }

    public function test_webhook_verification_requires_the_token(): void
    {
        $this->enableWhatsApp();

        $this->get('/webhook/whatsapp?hub_verify_token=yanlis&hub_challenge=123')->assertForbidden();

        $this->get('/webhook/whatsapp?hub_verify_token=dogrulama&hub_challenge=123')
            ->assertOk()
            ->assertSee('123');
    }

    public function test_webhook_rejects_events_without_a_valid_signature(): void
    {
        $this->enableWhatsApp();
        $reservation = $this->makeReservation();

        $payload = $this->buttonPayload(TemplateRegistry::BUTTON_APPROVE, 'wamid.FORGED', $reservation->owner->whatsapp_no);

        // Imza hic yok
        $this->postJson('/webhook/whatsapp', $payload)->assertForbidden();

        // Imza var ama yanlis (baska bir secret ile uretilmis)
        $badSignature = 'sha256='.hash_hmac('sha256', json_encode($payload), 'yanlis-secret');
        $this->call('POST', '/webhook/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Hub-Signature-256' => $badSignature,
        ], json_encode($payload))->assertForbidden();

        // Sahte istek rezervasyonu etkilememis olmali
        $this->assertSame(ReservationStatus::Pending, $reservation->refresh()->status);
    }

    public function test_owner_button_reply_approves_the_reservation(): void
    {
        $this->enableWhatsApp();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.OWNER']]])]);

        $reservation = $this->makeReservation();

        $this->postSignedWebhook($this->buttonPayload(
            TemplateRegistry::BUTTON_APPROVE,
            'wamid.OWNER',
            '905001112233'
        ))->assertOk();

        $reservation->refresh();

        $this->assertSame(ReservationStatus::Approved, $reservation->status);

        $log = $reservation->logs()->where('action', 'approved')->firstOrFail();
        $this->assertSame('whatsapp', $log->channel);
    }

    public function test_owner_button_reply_can_reject(): void
    {
        $this->enableWhatsApp();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.R']]])]);

        $reservation = $this->makeReservation();

        $this->postSignedWebhook($this->buttonPayload(
            TemplateRegistry::BUTTON_REJECT,
            'wamid.R',
            '905001112233'
        ))->assertOk();

        $this->assertSame(ReservationStatus::Rejected, $reservation->refresh()->status);
    }

    public function test_second_button_press_does_not_break_anything(): void
    {
        $this->enableWhatsApp();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.D']]])]);

        $reservation = $this->makeReservation();
        $payload = $this->buttonPayload(TemplateRegistry::BUTTON_APPROVE, 'wamid.D', '905001112233');

        $this->postSignedWebhook($payload)->assertOk();
        $this->postSignedWebhook($payload)->assertOk();

        $reservation->refresh();

        $this->assertSame(ReservationStatus::Approved, $reservation->status);
        $this->assertSame(1, $reservation->logs()->where('action', 'approved')->count());
    }

    public function test_delivery_and_read_statuses_update_the_message_log(): void
    {
        $this->enableWhatsApp();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.S']]])]);

        $this->makeReservation();

        $this->postSignedWebhook($this->statusPayload('wamid.S', 'delivered'))->assertOk();
        $log = MessageLog::where('provider_message_id', 'wamid.S')->firstOrFail();
        $this->assertSame('delivered', $log->status);
        $this->assertNotNull($log->delivered_at);

        $this->postSignedWebhook($this->statusPayload('wamid.S', 'read'))->assertOk();
        $this->assertSame('read', $log->refresh()->status);

        $this->postSignedWebhook($this->statusPayload('wamid.S', 'failed'))->assertOk();
        $log->refresh();
        $this->assertSame('failed', $log->status);
        $this->assertNotNull($log->error);
    }

    public function test_unknown_payloads_are_ignored_quietly(): void
    {
        $this->enableWhatsApp();

        $this->postSignedWebhook(['entry' => []])->assertOk();
        $this->postSignedWebhook($this->statusPayload('bilinmeyen-id', 'delivered'))->assertOk();
        $this->postSignedWebhook([])->assertOk();
    }

    public function test_phone_numbers_are_normalised_to_e164_digits(): void
    {
        $client = app(WhatsAppClient::class);

        $this->assertSame('905551112233', $client->normalizeNumber('+90 555 111 22 33'));
        $this->assertSame('905551112233', $client->normalizeNumber('0555 111 22 33'));
        $this->assertSame('905551112233', $client->normalizeNumber('0090-555-111-22-33'));
        $this->assertSame('4915112345678', $client->normalizeNumber('+49 151 12345678'));
    }

    public function test_every_template_has_matching_parameter_count(): void
    {
        $reservation = $this->makeReservation();

        foreach (TemplateRegistry::definitions() as $key => $definition) {
            foreach (['tr', 'en'] as $locale) {
                $expected = substr_count($definition['body'][$locale], '{{');
                $actual = count(TemplateRegistry::paramsFor($key, $reservation, $locale));

                $this->assertSame(
                    $expected,
                    $actual,
                    "[{$key}/{$locale}] şablon {$expected} parametre bekliyor, kod {$actual} üretiyor."
                );
            }
        }
    }

    private function buttonPayload(string $text, string $contextId, string $from): array
    {
        return [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => $from,
                            'id' => 'wamid.INCOMING',
                            'type' => 'button',
                            'button' => ['payload' => $text, 'text' => $text],
                            'context' => ['id' => $contextId],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    private function statusPayload(string $id, string $status): array
    {
        return [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => $id,
                            'status' => $status,
                            'errors' => $status === 'failed'
                                ? [['title' => 'Message undeliverable']]
                                : null,
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    private function makeReservation(): Reservation
    {
        $this->post('/rezervasyon-talebi', [
            'yacht_id' => $this->yacht->id,
            'date' => now()->addDays(30)->toDateString(),
            'adults' => 6,
            'children' => 0,
            'customer_name' => 'Test Musteri',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+905551112233',
            'kvkk' => '1',
            'whatsapp_consent' => '1',
        ] + $this->korumaAlanlari());

        return Reservation::latest('id')->firstOrFail();
    }
}

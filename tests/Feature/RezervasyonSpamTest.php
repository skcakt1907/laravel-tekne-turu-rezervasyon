<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Yacht;
use App\Support\FormKorumasi;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\FormKorumasiVerisi;
use Tests\TestCase;

/**
 * REZERVASYON FORMU BOT KORUMASI.
 *
 * Buradaki risk iletişim formundan büyük: sahte bir rezervasyon yalnızca
 * veritabanını kirletmekle kalmaz, her biri için müşteriye ve yönetime
 * e-posta + WhatsApp gider. O yüzden hiç kayıt açılmadığı kadar, hiç
 * bildirim gitmediği de sınanıyor.
 */
class RezervasyonSpamTest extends TestCase
{
    use FormKorumasiVerisi;
    use RefreshDatabase;

    private Yacht $tur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->tur = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();

        RateLimiter::clear('rezervasyon:127.0.0.1');
        Mail::fake();
    }

    /** @param array<string, mixed> $ustuneYaz */
    private function gonder(array $ustuneYaz = [], int $saniyeOnce = 60)
    {
        return $this->post('/rezervasyon-talebi', array_merge([
            'yacht_id' => $this->tur->id,
            'date' => now()->addDays(30)->toDateString(),
            'adults' => 4,
            'children' => 0,
            'customer_name' => 'Ali Veli',
            'customer_email' => 'ali@ornek.com',
            'customer_phone' => '+905551112233',
            'kvkk' => '1',
            'whatsapp_consent' => '1',
        ], $this->korumaAlanlari($saniyeOnce), $ustuneYaz));
    }

    /* ─────────── GERÇEK MÜŞTERİ GEÇMELİ ─────────── */

    public function test_normal_rezervasyon_olusuyor(): void
    {
        $this->gonder()->assertRedirect();

        $this->assertSame(1, Reservation::count());
    }

    public function test_tur_sayfasi_koruma_alanlarini_iceriyor(): void
    {
        $this->get('/tur/'.$this->tur->slug)
            ->assertOk()
            ->assertSee('name="'.FormKorumasi::TUZAK.'"', false)
            ->assertSee('name="'.FormKorumasi::ZAMAN.'"', false);
    }

    /** Aynı kişi birkaç tur için form doldurabilir — sınır 5 */
    public function test_ayni_kisi_ucuncu_rezervasyonu_yapabiliyor(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->gonder(['date' => now()->addDays(30 + $i)->toDateString()])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, Reservation::count());
    }

    /* ─────────── BOT GEÇMEMELİ ─────────── */

    public function test_tuzak_alan_doldurulunca_rezervasyon_acilmiyor(): void
    {
        $this->gonder([FormKorumasi::TUZAK => 'http://spam.example'])
            ->assertSessionHasErrors('customer_email');

        $this->assertSame(0, Reservation::count());
    }

    public function test_aninda_gonderilen_form_rezervasyon_acmiyor(): void
    {
        $this->gonder(saniyeOnce: 0);

        $this->assertSame(0, Reservation::count());
    }

    public function test_bozuk_zaman_damgasi_rezervasyon_acmiyor(): void
    {
        $this->gonder([FormKorumasi::ZAMAN => 'uydurma']);

        $this->assertSame(0, Reservation::count());
    }

    public function test_cok_baglantili_mesaj_rezervasyon_acmiyor(): void
    {
        $this->gonder([
            'message' => 'SEO: http://spam1.example ve www.spam2.example ayrica spam3.xyz',
        ]);

        $this->assertSame(0, Reservation::count());
    }

    /**
     * Asıl mesele bu: engellenen gönderimden HİÇ BİLDİRİM GİTMEMELİ.
     * Kayıt açılmasa bile mail/WhatsApp gidiyorsa spam yine rahatsız eder.
     */
    public function test_engellenen_gonderimden_bildirim_gitmiyor(): void
    {
        $this->gonder([FormKorumasi::TUZAK => 'spam']);

        Mail::assertNothingSent();
        $this->assertSame(0, \App\Models\MessageLog::count());
    }

    public function test_saatte_besten_fazla_rezervasyon_engelleniyor(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->gonder(['date' => now()->addDays(30 + $i)->toDateString()])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(5, Reservation::count());

        $this->gonder(['date' => now()->addDays(40)->toDateString()])
            ->assertSessionHasErrors('customer_email');

        $this->assertSame(5, Reservation::count());
    }
}

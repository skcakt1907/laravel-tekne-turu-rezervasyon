<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Support\FormKorumasi;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * İLETİŞİM FORMU BOT KORUMASI.
 *
 * Testlerin yarısı korumanın botu tuttuğunu, diğer yarısı GERÇEK
 * MÜŞTERİYİ ENGELLEMEDİĞİNİ gösteriyor. İkincisi daha önemli: fazla
 * sıkı bir koruma, spam'den daha pahalıya mal olur — gelen iş kaybolur
 * ve kimse fark etmez.
 */
class IletisimSpamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        RateLimiter::clear('iletisim:127.0.0.1');
    }

    /** @param array<string, mixed> $ustuneYaz */
    private function gonder(array $ustuneYaz = [], int $saniyeOnce = 30)
    {
        return $this->post('/iletisim', array_merge([
            'name' => 'Ali Veli',
            'email' => 'ali@ornek.com',
            'phone' => '05551112233',
            'subject' => 'Tur hakkında',
            'message' => 'Merhaba, 12 kişilik bir grup için fiyat öğrenebilir miyim?',
            FormKorumasi::TUZAK => '',
            FormKorumasi::ZAMAN => Crypt::encryptString((string) (time() - $saniyeOnce)),
        ], $ustuneYaz));
    }

    /* ─────────── GERÇEK MÜŞTERİ GEÇMELİ ─────────── */

    public function test_normal_mesaj_kaydediliyor(): void
    {
        $this->gonder()->assertRedirect();

        $this->assertSame(1, ContactMessage::count());
        $this->assertSame('ali@ornek.com', ContactMessage::first()->email);
    }

    /** Müşteri kendi sitesinin adresini yazabilir — tek bağlantı sorun değil */
    public function test_tek_baglanti_iceren_mesaj_gecer(): void
    {
        $this->gonder(['message' => 'Merhaba, firmamız acentedir: www.ornekacente.com — is birligi yapalim mi?']);

        $this->assertSame(1, ContactMessage::count());
    }

    /** Form sayfası tuzak alanı ve zaman damgasını basıyor mu */
    public function test_form_sayfasi_koruma_alanlarini_iceriyor(): void
    {
        $this->get('/iletisim')
            ->assertOk()
            ->assertSee('name="' . FormKorumasi::TUZAK . '"', false)
            ->assertSee('name="' . FormKorumasi::ZAMAN . '"', false);
    }

    /* ─────────── BOT GEÇMEMELİ ─────────── */

    public function test_tuzak_alan_doldurulunca_kayit_acilmiyor(): void
    {
        $this->gonder([FormKorumasi::TUZAK => 'http://spam.example'])->assertRedirect();

        $this->assertSame(0, ContactMessage::count());
    }

    /**
     * Bot'a hata gosterilmemeli: hangi alanın ele verdiğini deneyerek
     * bulmasın diye başarılı gönderimle aynı cevabı alıyor.
     */
    public function test_bot_hata_gormuyor_basarili_sanip_gidiyor(): void
    {
        $this->gonder([FormKorumasi::TUZAK => 'spam'])
            ->assertRedirect()
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();
    }

    public function test_aninda_gonderilen_form_kaydedilmiyor(): void
    {
        $this->gonder(saniyeOnce: 0);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_zaman_damgasi_olmayan_gonderim_kaydedilmiyor(): void
    {
        $this->gonder([FormKorumasi::ZAMAN => '']);

        $this->assertSame(0, ContactMessage::count());
    }

    /** Baska bir sitede uretilmis/kurcalanmis damga cozulemez */
    public function test_bozuk_zaman_damgasi_kaydedilmiyor(): void
    {
        $this->gonder([FormKorumasi::ZAMAN => 'uydurma-deger']);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_cok_baglantili_mesaj_kaydedilmiyor(): void
    {
        $this->gonder([
            'message' => 'SEO hizmeti: http://spam1.example ayrica www.spam2.example ve spam3.xyz adreslerine bakin.',
        ]);

        $this->assertSame(0, ContactMessage::count());
    }

    /* ─────────── SAYI SINIRI ─────────── */

    public function test_saatte_ucten_fazla_mesaj_engelleniyor(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->gonder(['email' => "musteri{$i}@ornek.com"])->assertSessionHasNoErrors();
        }

        $this->assertSame(3, ContactMessage::count());

        // Dorduncude gercek bir hata mesaji gorunur -- burada sessiz
        // kalmak yanlis olurdu, gonderen kisi neden basarisiz oldugunu
        // bilmeli.
        $this->gonder(['email' => 'musteri4@ornek.com'])
            ->assertSessionHasErrors('message');

        $this->assertSame(3, ContactMessage::count());
    }
}

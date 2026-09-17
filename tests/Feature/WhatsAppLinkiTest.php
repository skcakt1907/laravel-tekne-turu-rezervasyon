<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\WhatsAppLinki;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SİTEDE GÖRÜNEN WHATSAPP LİNKİ.
 *
 * Buradaki asıl risk sessiz hata: yanlış bir numaraya link üretilirse
 * müşterinin mesajı bilinmeyen birine gider ve kimse fark etmez.
 * Bu yüzden "emin olamadığında link verme" davranışı test ediliyor.
 */
class WhatsAppLinkiTest extends TestCase
{
    use RefreshDatabase;

    /* ─────────── NUMARA ÇÖZÜMLEME ─────────── */

    /** Panele nasıl yazılırsa yazılsın aynı numaraya çıkmalı */
    public function test_ayni_numaranin_farkli_yazimlari_ayni_sonucu_veriyor(): void
    {
        foreach ([
            '0532 296 47 85',
            '05322964785',
            '+90 532 296 47 85',
            '905322964785',
            '0(532) 296-47-85',
            '  0532 296 47 85  ',
        ] as $yazim) {
            $this->assertSame('905322964785', WhatsAppLinki::numara($yazim), "Yazım: {$yazim}");
        }
    }

    /** Ülke kodu yazılmamış cep numarasına 90 eklenir */
    public function test_ulke_kodsuz_cep_numarasina_90_ekleniyor(): void
    {
        $this->assertSame('905322964785', WhatsAppLinki::numara('5322964785'));
    }

    /** Yurt dışı numara yalnızca + ile yazılırsa kabul edilir */
    public function test_yurt_disi_numara_arti_ile_kabul_ediliyor(): void
    {
        $this->assertSame('442079460958', WhatsAppLinki::numara('+44 20 7946 0958'));
    }

    /**
     * + olmadan yazılan yabancı numarada ülke kodunun olup olmadığı
     * bilinemez; tahmin yürütmek yanlış numaraya link üretir.
     */
    public function test_artisiz_yabanci_numara_reddediliyor(): void
    {
        $this->assertNull(WhatsAppLinki::numara('442079460958'));
    }

    /** Anlaşılamayan girdide link üretilmez */
    public function test_bozuk_girdi_null_donuyor(): void
    {
        foreach (['', '   ', 'abc', '123', '5551234', null] as $girdi) {
            $this->assertNull(WhatsAppLinki::numara($girdi), 'Girdi: ' . var_export($girdi, true));
        }
    }

    /* ─────────── ADRES ─────────── */

    public function test_wa_me_adresi_uretiliyor(): void
    {
        $this->assertSame('https://wa.me/905322964785', WhatsAppLinki::url('0532 296 47 85'));
    }

    public function test_hazir_mesaj_adrese_ekleniyor(): void
    {
        $this->assertSame(
            'https://wa.me/905322964785?text=Merhaba%2C%20tur%20hakk%C4%B1nda',
            WhatsAppLinki::url('0532 296 47 85', 'Merhaba, tur hakkında')
        );
    }

    public function test_bos_mesaj_adrese_eklenmiyor(): void
    {
        $this->assertSame('https://wa.me/905322964785', WhatsAppLinki::url('05322964785', '   '));
    }

    public function test_cozulemeyen_numarada_adres_uretilmiyor(): void
    {
        $this->assertNull(WhatsAppLinki::url('abc'));
    }

    /* ─────────── AYARLARDAN OKUMA ─────────── */

    /** WhatsApp alanı doluysa o kullanılır */
    public function test_whatsapp_alani_oncelikli(): void
    {
        Setting::put('site_phone', '0212 111 22 33');
        Setting::put('whatsapp_display_number', '0532 296 47 85');
        Setting::flush();

        $this->assertSame('https://wa.me/905322964785', WhatsAppLinki::sitedeki());
    }

    /**
     * WhatsApp alanı boşsa site telefonuna düşülür — çoğu işletmede
     * ikisi aynı numara, iki kere yazdırmanın anlamı yok.
     */
    public function test_whatsapp_alani_bossa_site_telefonuna_dusuluyor(): void
    {
        Setting::put('site_phone', '0532 296 47 85');
        Setting::put('whatsapp_display_number', '');
        Setting::flush();

        $this->assertSame('https://wa.me/905322964785', WhatsAppLinki::sitedeki());
    }

    /** İkisi de boşsa hiç link üretilmez */
    public function test_ikisi_de_bossa_link_yok(): void
    {
        Setting::put('site_phone', '');
        Setting::put('whatsapp_display_number', '');
        Setting::flush();

        $this->assertNull(WhatsAppLinki::sitedeki());
    }

    /* ─────────── EKRANDA ─────────── */

    /** Numara tanımlıysa sabit buton ve menü linki basılmalı */
    public function test_numara_varsa_sayfada_link_gorunuyor(): void
    {
        Setting::put('site_phone', '0532 296 47 85');
        Setting::put('whatsapp_display_number', '0532 296 47 85');
        Setting::flush();

        $yanit = $this->get('/');

        $yanit->assertOk();
        $yanit->assertSee('https://wa.me/905322964785', false);
        $yanit->assertSee('id="wa-sabit"', false);
    }

    /** Numara yoksa hiçbir WhatsApp ögesi basılmamalı — boş link kalmasın */
    public function test_numara_yoksa_sayfada_link_gorunmuyor(): void
    {
        Setting::put('site_phone', '');
        Setting::put('whatsapp_display_number', '');
        Setting::flush();

        $yanit = $this->get('/');

        $yanit->assertOk();
        $yanit->assertDontSee('wa.me', false);
        $yanit->assertDontSee('id="wa-sabit"', false);
    }
}

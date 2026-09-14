<?php

namespace Tests\Feature;

use App\Models\Yacht;
use App\Models\YachtPhoto;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TUR KARTININ KAPAĞI — fotoğraf ya da video.
 *
 * Video kapak iki şarta bağlı: poster karesi olacak, ve og:image gibi
 * yalnızca resim kabul eden yerlere mp4 sızmayacak. Testlerin çoğu bu
 * ikisini koruyor.
 */
class YachtCoverMediaTest extends TestCase
{
    use RefreshDatabase;

    private Yacht $tur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->tur = Yacht::firstOrFail();
        $this->tur->photos()->delete();
    }

    private function fotograf(array $ustuneYaz = []): YachtPhoto
    {
        return $this->tur->photos()->create(array_merge([
            'path' => 'yachts/tekne.jpg',
            'sort' => 0,
        ], $ustuneYaz));
    }

    private function video(array $ustuneYaz = []): YachtPhoto
    {
        return $this->tur->photos()->create(array_merge([
            'path' => 'yachts/tanitim.mp4',
            'poster' => 'yachts/tanitim-kare.jpg',
            'sort' => 1,
        ], $ustuneYaz));
    }

    /* ─────────── TÜR TESPİTİ ─────────── */

    public function test_uzantidan_tur_belirleniyor(): void
    {
        $this->assertFalse($this->fotograf()->isVideo());
        $this->assertTrue($this->video()->isVideo());
    }

    /* ─────────── VİDEO KAPAK ─────────── */

    public function test_posterli_video_kapak_olabilir(): void
    {
        $video = $this->video(['is_cover' => true]);

        $this->assertTrue($video->fresh()->is_cover);
        $this->assertTrue($this->tur->fresh()->coverMedia()->isVideo());
    }

    /**
     * Postersiz video kapak olamaz: mobilde ve yavaş bağlantıda video hiç
     * indirilmiyor, gösterilecek tek şey poster. Poster yoksa kart boş kare
     * olurdu.
     */
    public function test_postersiz_video_kapak_olamaz(): void
    {
        $video = $this->video(['poster' => null, 'is_cover' => true]);

        $this->assertFalse($video->fresh()->is_cover);
    }

    /** Fotoğrafta poster kavramı yok — yanlışlıkla girilse bile temizlenir */
    public function test_fotografin_posteri_temizlenir(): void
    {
        $foto = $this->fotograf(['poster' => 'yachts/bir-sey.jpg']);

        $this->assertNull($foto->fresh()->poster);
    }

    /* ─────────── GÖRSEL ADRESİ ─────────── */

    /**
     * og:image, yapısal veri ve panel listesi coverUrl()'den besleniyor;
     * oralara mp4 verilemez. Kapak videoysa poster karesi dönmeli.
     */
    public function test_video_kapakta_gorsel_adresi_poster_doner(): void
    {
        $this->video(['is_cover' => true]);

        $adres = $this->tur->fresh()->coverUrl();

        $this->assertStringContainsString('tanitim-kare.jpg', $adres);
        $this->assertStringNotContainsString('.mp4', $adres);
    }

    public function test_kapak_secilmemisse_ilk_fotograf_kullanilir(): void
    {
        $this->video();
        $this->fotograf(['path' => 'yachts/ikinci.jpg', 'sort' => 2]);

        $this->assertStringContainsString('ikinci.jpg', $this->tur->fresh()->coverUrl());
    }

    /* ─────────── KART ─────────── */

    public function test_video_kapakli_kartta_video_etiketi_basiliyor(): void
    {
        $this->video(['is_cover' => true]);

        $cevap = $this->get('/turlar');

        $cevap->assertOk()
            ->assertSee('tanitim.mp4', false)
            // Sayfa açılırken video İNMEMELİ; sekiz turlu listede sekiz
            // video birden indirilmesin diye hover'a bağlı.
            ->assertSee('preload="none"', false)
            ->assertSee('poster="', false);
    }

    public function test_fotograf_kapakli_kartta_video_etiketi_yok(): void
    {
        $this->fotograf(['is_cover' => true]);

        $this->get('/turlar')
            ->assertOk()
            ->assertSee('tekne.jpg', false)
            ->assertDontSee('<video', false);
    }
}

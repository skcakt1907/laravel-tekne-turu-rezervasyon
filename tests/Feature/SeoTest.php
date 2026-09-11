<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\Yacht;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private Yacht $yacht;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->yacht = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();
    }

    public function test_sitemap_lists_public_pages_with_alternates(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()->assertHeader('Content-Type', 'application/xml');

        $xml = $response->getContent();

        $this->assertStringContainsString('<loc>'.url('/').'</loc>', $xml);
        $this->assertStringContainsString(url('/tur/'.$this->yacht->slug), $xml);
        $this->assertStringContainsString(url('/sayfa/kvkk'), $xml);

        // Her adres icin EN karsiligi
        $this->assertStringContainsString('hreflang="en"', $xml);
        $this->assertStringContainsString(url('/en/tur/'.$this->yacht->slug), $xml);

        // Gecerli XML olmali
        $this->assertInstanceOf(\SimpleXMLElement::class, simplexml_load_string($xml));
    }

    public function test_unpublished_yachts_stay_out_of_the_sitemap(): void
    {
        $this->yacht->forceFill(['status' => \App\Enums\YachtStatus::Draft])->save();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee(url('/tur/'.$this->yacht->slug));
    }

    public function test_robots_blocks_panels_and_personal_pages(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        foreach (['/yonetim', '/hesabim', '/rezervasyon/', '/onay/'] as $path) {
            $this->assertStringContainsString('Disallow: '.$path, $robots);
        }

        $this->assertStringContainsString('Sitemap:', $robots);
    }

    public function test_yacht_page_has_product_and_breadcrumb_schema(): void
    {
        $response = $this->get('/tur/'.$this->yacht->slug);

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"Product"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
        // Odeme alinmadigi icin stok degil on talep
        $this->assertStringContainsString('PreOrder', $html);
    }

    public function test_personal_pages_are_noindex(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/hesabim')
            ->assertOk()
            ->assertSee('content="noindex, nofollow"', false);

        $this->get('/')->assertOk()->assertSee('content="index, follow"', false);
    }

    public function test_analytics_script_is_absent_until_an_id_is_set(): void
    {
        $this->get('/')->assertOk()->assertDontSee('googletagmanager.com');

        Setting::put('google_analytics_id', 'G-TEST12345');

        // Betik sayfada tanimli ama yalnizca onay verilirse yukleniyor
        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('G-TEST12345', $html);
        $this->assertStringContainsString('cookie-consent', $html);
    }

    /**
     * Testler `array` onbellek surucusuyle kosar ve orada serilestirme HIC olmaz.
     * Gercek kurulumda (database/file) Laravel 13 onbellekten hicbir PHP sinifini
     * geri acmaz (serializable_classes = false) -> Eloquent koleksiyonu onbellege
     * alinirsa sayfa 500 verir. Bu test onu yakalamak icin gercek suruculerle kosar.
     */
    public function test_public_pages_work_with_a_serializing_cache_store(): void
    {
        foreach (['file', 'database'] as $store) {
            config(['cache.default' => $store]);
            Cache::store($store)->clear();

            $this->get('/')->assertOk()->assertSee('Mavi Rüzgar');
            $this->get('/')->assertOk()->assertSee('Mavi Rüzgar'); // ikinci istek: varsa onbellekten
            $this->get('/turlar')->assertOk();
            $this->get('/tur/'.$this->yacht->slug)->assertOk();
        }
    }

    public function test_home_page_reflects_listing_changes_immediately(): void
    {
        $this->get('/')->assertOk()->assertSee('Mavi Rüzgar');

        $this->yacht->forceFill(['status' => \App\Enums\YachtStatus::Draft])->save();

        $this->get('/')->assertOk()->assertDontSee('Mavi Rüzgar');
    }

    public function test_legal_pages_have_real_content(): void
    {
        foreach (['kvkk', 'kullanim-kosullari', 'cerez-politikasi', 'iptal-politikasi'] as $slug) {
            $response = $this->get('/sayfa/'.$slug);

            $response->assertOk();
            $this->assertStringNotContainsString('İçerik hazırlanıyor', $response->getContent());
        }

        $this->get('/sayfa/kvkk')->assertSee('6698', false);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\RelationManagers\CustomerNotesRelationManager;
use App\Filament\Resources\Customers\RelationManagers\ReservationsRelationManager;
use App\Models\Customer;
use App\Models\CustomerNote;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Yacht;
use App\Services\ReservationService;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * MÜŞTERİ CRM'i — üyelikten bağımsız.
 *
 * Buradaki asıl sınav: rezervasyon formu dolduran kişi hesap açmadan
 * müşteri kaydına dönüşüyor mu ve aynı kişi ikinci kez geldiğinde
 * ikinci kayıt açılmıyor mu.
 */
class CustomerCrmTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Yacht $tur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Filament::setCurrentPanel('admin');

        $this->admin = User::where('email', 'admin@yatkiralama.com')->firstOrFail();
        $this->tur = Yacht::firstOrFail();

        $this->actingAs($this->admin);
    }

    /** Rezervasyon verisi — testler arasında yalnızca e-posta/ad değişiyor. */
    private function rezervasyon(array $ustuneYaz = []): Reservation
    {
        return app(ReservationService::class)->request($this->tur, array_merge([
            'customer_name' => 'Ali Veli',
            'customer_email' => 'ali@ornek.com',
            'customer_phone' => '05551112233',
            'customer_locale' => 'tr',
            'date' => now()->addDays(5)->toDateString(),
            'adults' => 2,
            'children' => 0,
        ], $ustuneYaz));
    }

    /* ─────────── MÜŞTERİ OLUŞUMU ─────────── */

    public function test_uyeliksiz_rezervasyon_musteri_kaydi_acar(): void
    {
        $rez = $this->rezervasyon();

        $musteri = Customer::where('email', 'ali@ornek.com')->firstOrFail();

        $this->assertNull($rez->user_id, 'üyelik yok, user_id boş kalmalı');
        $this->assertSame($musteri->id, $rez->customer_id);
        $this->assertSame('Ali Veli', $musteri->name);
        $this->assertSame('05551112233', $musteri->phone);
    }

    /**
     * CRM'in var olma sebebi bu: aynı kişi ikinci kez rezervasyon
     * yaptığında yeni kayıt açılmamalı, ikisi de aynı müşteride toplanmalı.
     */
    public function test_ayni_eposta_ikinci_kez_gelince_yeni_musteri_acilmaz(): void
    {
        $this->rezervasyon();
        $this->rezervasyon(['date' => now()->addDays(9)->toDateString()]);

        $this->assertSame(1, Customer::where('email', 'ali@ornek.com')->count());
        $this->assertSame(2, Customer::where('email', 'ali@ornek.com')->firstOrFail()
            ->reservations()->count());
    }

    /** Büyük/küçük harf farkı aynı kişiyi ikiye bölmemeli */
    public function test_eposta_buyuk_harfle_gelse_de_ayni_musteriye_baglanir(): void
    {
        $this->rezervasyon();
        $this->rezervasyon(['customer_email' => 'ALI@Ornek.com']);

        $this->assertSame(1, Customer::count());
    }

    /** İnsanlar numara değiştirir — en son verilen bilgi geçerli sayılır */
    public function test_sonraki_rezervasyon_telefonu_gunceller(): void
    {
        $this->rezervasyon();
        $this->rezervasyon(['customer_phone' => '05559998877']);

        $this->assertSame('05559998877', Customer::firstOrFail()->phone);
    }

    /** Boş gelen alan mevcut bilgiyi silmemeli */
    public function test_bos_whatsapp_mevcut_kaydi_silmez(): void
    {
        $this->rezervasyon(['customer_whatsapp' => '05551112233']);
        $this->rezervasyon(['customer_whatsapp' => null]);

        $this->assertSame('05551112233', Customer::firstOrFail()->whatsapp);
    }

    /* ─────────── PANEL ─────────── */

    public function test_musteriler_listesi_acilir(): void
    {
        $this->rezervasyon();

        Livewire::test(ListCustomers::class)
            ->assertSuccessful()
            ->assertSee('ali@ornek.com');
    }

    public function test_musteri_detayinda_crm_sekmeleri_var(): void
    {
        $this->rezervasyon();

        Livewire::test(EditCustomer::class, ['record' => Customer::firstOrFail()->getKey()])
            ->assertSuccessful()
            ->assertSee('Notlar')
            ->assertSee('Rezervasyonlar');
    }

    /**
     * Not eklerken user_id yazılmıyor; kolon NULL kabul etmiyorsa burada
     * patlar. (Üyelik kaldırılırken atlanması kolay bir ayrıntı.)
     */
    public function test_musteriye_not_eklenebilir(): void
    {
        $this->rezervasyon();
        $musteri = Customer::firstOrFail();

        Livewire::test(CustomerNotesRelationManager::class, [
            'ownerRecord' => $musteri,
            'pageClass' => EditCustomer::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'Telefon görüşmesi',
                'body' => 'Müşteri fiyat sordu, teklif gönderildi.',
            ])
            ->assertHasNoActionErrors();

        $not = CustomerNote::where('customer_id', $musteri->id)->firstOrFail();

        $this->assertSame('Telefon görüşmesi', $not->title);
        $this->assertSame($this->admin->id, $not->admin_id);
        $this->assertNull($not->user_id);
    }

    public function test_musterinin_rezervasyonlari_listelenir(): void
    {
        $rez = $this->rezervasyon();

        Livewire::test(ReservationsRelationManager::class, [
            'ownerRecord' => Customer::firstOrFail(),
            'pageClass' => EditCustomer::class,
        ])
            ->assertSuccessful()
            ->assertSee($rez->code);
    }

    /** Müşteri silinince rezervasyon kaybolmamalı — ticari kayıt o */
    public function test_musteri_silinince_rezervasyon_durur(): void
    {
        $rez = $this->rezervasyon();

        Customer::firstOrFail()->delete();

        $rez->refresh();

        $this->assertNotNull($rez->id);
        $this->assertNull($rez->customer_id);
        $this->assertSame('ali@ornek.com', $rez->customer_email);
    }
}

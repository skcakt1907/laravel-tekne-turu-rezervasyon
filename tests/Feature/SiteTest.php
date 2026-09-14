<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\BlockedPeriod;
use App\Models\Consent;
use App\Models\Reservation;
use App\Models\Yacht;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    private Yacht $yacht;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->yacht = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();
    }

    public function test_public_pages_load(): void
    {
        $this->get('/')->assertOk()->assertSee('Mavi Rüzgar');
        $this->get('/turlar')->assertOk();
        $this->get('/tur/'.$this->yacht->slug)->assertOk()->assertSee('Rezervasyon Talebi');
        $this->get('/iletisim')->assertOk();
        $this->get('/sayfa/kvkk')->assertOk();
        $this->get('/rezervasyon-sorgula')->assertOk();
    }

    public function test_english_pages_load_with_translations(): void
    {
        $this->get('/en')->assertOk()->assertSee('The boat tour you want is one click away');
        $this->get('/en/turlar')->assertOk()->assertSee('tours found', false);
        $this->get('/en/tur/'.$this->yacht->slug)->assertOk()->assertSee('Booking Request');
    }

    public function test_price_from_is_denormalised_for_sorting(): void
    {
        $this->assertSame('2400.00', $this->yacht->price_from);
        $this->assertSame('day', $this->yacht->price_from_unit);

        // En ucuz once
        $response = $this->get('/turlar?sort=price_asc');
        $response->assertOk();

        $positions = [
            'cheap' => strpos($response->getContent(), 'Deniz Yıldızı'),
            'expensive' => strpos($response->getContent(), 'Mavi Rüzgar'),
        ];

        $this->assertLessThan($positions['expensive'], $positions['cheap']);
    }

    public function test_filters_narrow_results(): void
    {
        $this->get('/turlar?type=gulet')->assertOk()->assertSee('Mavi Rüzgar')->assertDontSee('Deniz Yıldızı');
        $this->get('/turlar?cabins=6')->assertOk()->assertSee('Mavi Rüzgar')->assertDontSee('Deniz Yıldızı');
        $this->get('/turlar?guests=14')->assertOk()->assertSee('Mavi Rüzgar')->assertDontSee('Deniz Yıldızı');
        $this->get('/turlar?price_max=500')->assertOk()->assertSee('Deniz Yıldızı')->assertDontSee('Mavi Rüzgar');
    }

    public function test_date_filter_hides_yachts_with_approved_bookings(): void
    {
        $start = now()->addDays(20)->startOfDay()->setTime(10, 0);
        $end = $start->copy()->addDays(3);

        BlockedPeriod::create([
            'yacht_id' => $this->yacht->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'reason' => 'manual',
        ]);

        $query = '?start='.$start->toDateString().'&end='.$end->toDateString();

        $this->get('/turlar'.$query)->assertOk()->assertDontSee('Mavi Rüzgar');

        // Bloktan sonraki tarihlerde yine gorunmeli
        $free = '?start='.$end->copy()->addDays(5)->toDateString().'&end='.$end->copy()->addDays(8)->toDateString();
        $this->get('/turlar'.$free)->assertOk()->assertSee('Mavi Rüzgar');
    }

    public function test_booking_request_creates_pending_reservation_and_consents(): void
    {
        $date = now()->addDays(30)->toDateString();

        $response = $this->post('/rezervasyon-talebi', [
            'yacht_id' => $this->yacht->id,
            'date' => $date,
            'adults' => 4,
            'children' => 0,
            'customer_name' => 'Test Musteri',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+905551112233',
            'kvkk' => '1',
            'whatsapp_consent' => '1',
        ]);

        $reservation = Reservation::firstOrFail();

        $response->assertRedirect();
        $this->assertSame(ReservationStatus::Pending, $reservation->status);
        $this->assertSame('9600.00', $reservation->estimated_total); // 4 yetiskin x 2400
        $this->assertStringStartsWith('YK-', $reservation->code);

        // Talep kapasiteden dusmez (Mavi Ruzgar kapasitesi 16)
        $this->assertSame(16, app(\App\Services\AvailabilityService::class)->remainingSeats($this->yacht, \Illuminate\Support\Carbon::parse($date)));

        // Acik riza kayitlari (KVKK + WhatsApp)
        $this->assertSame(2, Consent::where('subject_id', $reservation->id)->count());
    }

    public function test_booking_request_requires_consent(): void
    {
        $date = now()->addDays(30)->toDateString();

        $this->post('/rezervasyon-talebi', [
            'yacht_id' => $this->yacht->id,
            'date' => $date,
            'adults' => 4,
            'children' => 0,
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+905551112233',
        ])->assertSessionHasErrors(['kvkk', 'whatsapp_consent']);

        $this->assertSame(0, Reservation::count());
    }

    public function test_reservation_page_requires_valid_token(): void
    {
        $reservation = $this->makeReservation();

        $this->get('/rezervasyon/'.$reservation->code)->assertForbidden();
        $this->get('/rezervasyon/'.$reservation->code.'?token=yanlis')->assertForbidden();
        $this->get('/rezervasyon/'.$reservation->code.'?token='.$reservation->access_token)
            ->assertOk()
            ->assertSee($reservation->code);
    }

    public function test_lookup_with_code_and_email_redirects_to_reservation(): void
    {
        $reservation = $this->makeReservation();

        $this->post('/rezervasyon-sorgula', [
            'code' => $reservation->code,
            'email' => $reservation->customer_email,
        ])->assertRedirect();

        $this->post('/rezervasyon-sorgula', [
            'code' => $reservation->code,
            'email' => 'baska@example.com',
        ])->assertSessionHasErrors('code');
    }

    public function test_owner_can_approve_from_secure_link(): void
    {
        $reservation = $this->makeReservation();

        $this->get("/onay/{$reservation->code}/{$reservation->access_token}")->assertOk();

        $this->post("/onay/{$reservation->code}/{$reservation->access_token}", [
            'decision' => 'approve',
        ])->assertRedirect();

        $reservation->refresh();

        $this->assertSame(ReservationStatus::Approved, $reservation->status);

        // Kapasiteden onaylanan kisi sayisi kadar dusmus olmali
        $remaining = app(\App\Services\AvailabilityService::class)->remainingSeats($this->yacht, $reservation->starts_at);
        $this->assertSame(16 - $reservation->guests, $remaining);

        // Ayni baglanti ikinci kez onay ekrani acmamali
        $this->get("/onay/{$reservation->code}/{$reservation->access_token}")->assertStatus(410);
    }

    public function test_contact_form_stores_message(): void
    {
        $this->post('/iletisim', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'message' => 'Merhaba',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', ['email' => 'test@example.com']);
    }

    private function makeReservation(): Reservation
    {
        $date = now()->addDays(40)->toDateString();

        $this->post('/rezervasyon-talebi', [
            'yacht_id' => $this->yacht->id,
            'date' => $date,
            'adults' => 6,
            'children' => 0,
            'customer_name' => 'Test Musteri',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+905551112233',
            'kvkk' => '1',
            'whatsapp_consent' => '1',
        ]);

        return Reservation::latest('id')->firstOrFail();
    }

    public function test_locale_is_decided_by_url_not_session(): void
    {
        // Once EN sayfasi gezilse bile oneksiz adres TR donmeli
        $this->get('/en')->assertOk()->assertSee('The boat tour you want is one click away');
        $this->get('/')->assertOk()->assertSee('İstediğiniz tekne turunuz bir tıklama uzağınızda');
        $this->get('/')->assertSee('<html lang="tr"', false);
        $this->get('/en')->assertSee('<html lang="en"', false);
    }
}

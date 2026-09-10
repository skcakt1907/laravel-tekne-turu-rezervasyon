<?php

namespace Tests\Feature;

use App\Models\BlockedPeriod;
use App\Models\Yacht;
use App\Services\AvailabilityService;
use App\Services\ReservationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Kisi basi grup turu musaitligi: kapasite, manuel blok ve onay etkisi. */
class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $availability;

    private Yacht $yacht;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Mail::fake();

        $this->availability = app(AvailabilityService::class);
        $this->yacht = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();
    }

    public function test_capacity_left_empty_means_no_seat_limit(): void
    {
        $this->yacht->forceFill(['capacity' => null])->save();
        $date = now()->addDays(10);

        // Kapasite bilinmiyorsa koltuk sayisi gosterilmez ama talep kabul edilir
        $this->assertNull($this->availability->remainingSeats($this->yacht->fresh(), $date));
        $this->assertTrue($this->availability->isAvailable($this->yacht->fresh(), $date, 40));
    }

    public function test_approved_reservations_consume_capacity(): void
    {
        $date = now()->addDays(12);
        $this->assertSame(16, $this->availability->remainingSeats($this->yacht, $date));

        $reservation = app(ReservationService::class)->request($this->yacht, [
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+905551112233',
            'date' => $date,
            'adults' => 10,
            'children' => 2,
        ]);

        // Talep kapasiteden dusmez
        $this->assertSame(16, $this->availability->remainingSeats($this->yacht->fresh(), $date));

        app(ReservationService::class)->approve($reservation);

        // Onay duser: 16 - 12 = 4
        $this->assertSame(4, $this->availability->remainingSeats($this->yacht->fresh(), $date));
        $this->assertTrue($this->availability->isAvailable($this->yacht->fresh(), $date, 4));
        $this->assertFalse($this->availability->isAvailable($this->yacht->fresh(), $date, 5));
    }

    public function test_manual_block_closes_the_whole_day(): void
    {
        $date = now()->addDays(15);

        BlockedPeriod::create([
            'yacht_id' => $this->yacht->id,
            'starts_at' => $date->copy()->startOfDay(),
            'ends_at' => $date->copy()->endOfDay(),
            'reason' => 'maintenance',
        ]);

        $this->assertFalse($this->availability->isAvailable($this->yacht, $date, 1));

        // Ertesi gun etkilenmemeli
        $this->assertTrue($this->availability->isAvailable($this->yacht, $date->copy()->addDay(), 1));
    }

    public function test_closed_listing_takes_no_requests(): void
    {
        $this->yacht->forceFill(['is_open' => false])->save();

        $this->assertFalse($this->availability->isAvailable($this->yacht->fresh(), now()->addDays(20), 1));
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Collection as CollectionModel;
use App\Models\CommissionSetting;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Yacht;
use App\Services\CollectionExporter;
use App\Services\CollectionService;
use App\Services\CommissionService;
use App\Services\ReservationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommissionTest extends TestCase
{
    use RefreshDatabase;

    private Yacht $yacht;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Mail::fake();

        $this->yacht = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();
        $this->owner = $this->yacht->owner;
    }

    public function test_narrowest_scope_wins(): void
    {
        $service = app(CommissionService::class);

        // Seeder genel %10 tanimladi
        $this->assertSame(10.0, $service->rateFor($this->yacht));

        CommissionSetting::create([
            'scope' => CommissionSetting::SCOPE_OWNER,
            'target_id' => $this->owner->id,
            'rate' => 15,
        ]);
        $this->assertSame(15.0, $service->rateFor($this->yacht));

        CommissionSetting::create([
            'scope' => CommissionSetting::SCOPE_YACHT,
            'target_id' => $this->yacht->id,
            'rate' => 7.5,
        ]);
        $this->assertSame(7.5, $service->rateFor($this->yacht));
    }

    public function test_rate_is_frozen_at_approval_and_later_changes_do_not_apply(): void
    {
        $reservation = app(ReservationService::class)->approve($this->makeReservation());

        $this->assertSame('10.00', $reservation->commission_rate);
        $this->assertSame('720.00', $reservation->commission_amount); // 7200 x %10

        // Oran sonradan degisirse gecmis kayit etkilenmemeli
        CommissionSetting::where('scope', CommissionSetting::SCOPE_GLOBAL)->update(['rate' => 25]);

        $reservation->refresh();
        $this->assertSame('10.00', $reservation->commission_rate);
        $this->assertSame('720.00', $reservation->commission_amount);
    }

    public function test_collection_totals_only_completed_reservations(): void
    {
        $completed = $this->completedReservation(3, 30);
        $this->cancelledReservation(3, 90);

        $collections = app(CollectionService::class)->build((int) $completed->starts_at->year, 3);

        $this->assertCount(1, $collections);

        $collection = $collections->first();
        $this->assertSame($this->owner->id, $collection->owner_id);
        $this->assertSame(1, $collection->reservation_count);
        $this->assertSame((float) $completed->estimated_total, (float) $collection->revenue);
        $this->assertSame((float) $completed->commission_amount, (float) $collection->commission);

        // Rezervasyon dokume baglanmali
        $this->assertSame($collection->id, $completed->refresh()->collection_id);
    }

    public function test_rebuilding_does_not_duplicate_or_overwrite_a_collected_period(): void
    {
        $reservation = $this->completedReservation(4, 30);
        $service = app(CollectionService::class);

        $year = (int) $reservation->starts_at->year;
        $first = $service->build($year, 4)->first();

        $service->markCollected($first, 'FTR-001');

        // Ayni donemi yeniden uretmek yeni kayit acmamali, tahsil edileni bozmamali
        $service->build($year, 4);

        $this->assertSame(1, CollectionModel::count());

        $first->refresh();
        $this->assertSame('collected', $first->status);
        $this->assertSame('FTR-001', $first->invoice_no);
    }

    public function test_build_command_uses_the_previous_month(): void
    {
        $last = now()->subMonthNoOverflow();
        $this->completedReservationOn($last->copy()->startOfMonth()->addDays(5));

        $this->artisan('collections:build')->assertSuccessful();

        $this->assertDatabaseHas('collections', [
            'owner_id' => $this->owner->id,
            'year' => (int) $last->year,
            'month' => (int) $last->month,
            'status' => 'pending',
        ]);
    }

    public function test_excel_export_produces_a_spreadsheet(): void
    {
        $reservation = $this->completedReservation(5, 30);
        $collection = app(CollectionService::class)->build((int) $reservation->starts_at->year, 5)->first();

        $response = app(CollectionExporter::class)->detail($collection);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // xlsx bir zip arsividir
        $this->assertStringStartsWith('PK', $content);
        $this->assertGreaterThan(500, strlen($content));
    }

    public function test_admin_can_see_finance_pages(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();

        $this->actingAs($admin);

        $this->get('/yonetim/commission-settings')->assertSuccessful();
        $this->get('/yonetim/commission-settings/create')->assertSuccessful();
        $this->get('/yonetim/collections')->assertSuccessful();
    }

    public function test_owner_cannot_reach_finance_pages(): void
    {
        $owner = User::create([
            'name' => 'Sahip',
            'email' => 'finans@example.com',
            'password' => Hash::make('password123'),
        ]);
        $owner->forceFill(['role' => UserRole::Owner, 'is_approved' => true])->save();

        $this->actingAs($owner->fresh())->get('/yonetim/collections')->assertForbidden();
    }

    private function completedReservation(int $month, int $dayOffset): Reservation
    {
        $reservation = app(ReservationService::class)->approve($this->makeReservation($dayOffset));

        $start = now()->setDate((int) now()->year, $month, 10)->setTime(10, 0);

        $reservation->forceFill([
            'status' => ReservationStatus::Completed,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addDays(3),
            'completed_at' => now(),
        ])->save();

        return $reservation->refresh();
    }

    private function completedReservationOn(\Illuminate\Support\Carbon $date): Reservation
    {
        $reservation = app(ReservationService::class)->approve($this->makeReservation(200));

        $reservation->forceFill([
            'status' => ReservationStatus::Completed,
            'starts_at' => $date,
            'ends_at' => $date->copy()->addDays(2),
            'completed_at' => now(),
        ])->save();

        return $reservation->refresh();
    }

    private function cancelledReservation(int $month, int $dayOffset): Reservation
    {
        $reservation = $this->makeReservation($dayOffset);

        $start = now()->setDate((int) now()->year, $month, 20)->setTime(10, 0);

        $reservation->forceFill([
            'status' => ReservationStatus::Cancelled,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addDays(2),
            'estimated_total' => 50000,
            'commission_amount' => 5000,
        ])->save();

        return $reservation;
    }

    private function makeReservation(int $dayOffset = 30): Reservation
    {
        $start = now()->addDays($dayOffset)->setTime(10, 0);

        return app(ReservationService::class)->request($this->yacht, [
            'customer_name' => 'Test Musteri',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+905551112233',
            'unit' => 'day',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addDays(3),
            'guests' => 6,
        ]);
    }
}

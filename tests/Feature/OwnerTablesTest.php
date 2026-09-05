<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\YachtStatus;
use App\Filament\Owner\Resources\BlockedPeriods\Pages\ListBlockedPeriods;
use App\Filament\Owner\Resources\Reservations\Pages\ListReservations;
use App\Filament\Owner\Resources\Yachts\Pages\ListYachts;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Yacht;
use App\Services\ReservationService;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** Yat sahibi panelinde filtre ve aksiyonlar — kapsam daraltmasıyla birlikte. */
class OwnerTablesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Yacht $yacht;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Filament::setCurrentPanel('owner');
        Mail::fake();

        $this->owner = User::where('email', 'sahip@yatkiralama.com')->firstOrFail();
        $this->yacht = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();

        $this->actingAs($this->owner);
    }

    public function test_owner_tables_filter_without_error(): void
    {
        Livewire::test(ListReservations::class)
            ->filterTable('status', [ReservationStatus::Pending->value])
            ->assertOk()
            ->filterTable('pending', true)
            ->assertOk()
            ->filterTable('cancel_requested', true)
            ->assertOk();

        Livewire::test(ListBlockedPeriods::class)
            ->filterTable('reason', 'manual')
            ->assertOk();
    }

    public function test_owner_can_submit_a_listing_for_approval(): void
    {
        $this->yacht->forceFill(['status' => YachtStatus::Draft])->save();

        // Fotograf sarti saglanmadan onaya gonderilemez
        Livewire::test(ListYachts::class)
            ->callTableAction('submit', $this->yacht)
            ->assertOk();
        $this->assertSame(YachtStatus::Draft, $this->yacht->refresh()->status);

        for ($i = 0; $i < (int) config('yacht.min_photos'); $i++) {
            $this->yacht->photos()->create(['path' => "yachts/o-{$i}.jpg", 'sort' => $i]);
        }

        Livewire::test(ListYachts::class)
            ->callTableAction('submit', $this->yacht)
            ->assertOk();
        $this->assertSame(YachtStatus::Pending, $this->yacht->refresh()->status);
    }

    public function test_owner_can_approve_and_reject_from_the_table(): void
    {
        $approve = $this->makeReservation(20);

        Livewire::test(ListReservations::class)
            ->callTableAction('approve', $approve)
            ->assertOk();

        $this->assertSame(ReservationStatus::Approved, $approve->refresh()->status);

        $reject = $this->makeReservation(60);

        Livewire::test(ListReservations::class)
            ->callTableAction('reject', $reject, ['reason' => 'Yat bakimda'])
            ->assertOk();

        $this->assertSame(ReservationStatus::Rejected, $reject->refresh()->status);
    }

    public function test_owner_tables_hide_other_owners_records(): void
    {
        $other = User::create([
            'name' => 'Diger',
            'email' => 'diger2@example.com',
            'password' => 'password123',
        ]);
        $other->forceFill(['role' => \App\Enums\UserRole::Owner, 'is_approved' => true])->save();

        $otherYacht = Yacht::create([
            'owner_id' => $other->id,
            'name' => ['tr' => 'Gizli Yat'],
            'slug' => 'gizli-yat',
            'type' => 'motoryat',
            'capacity' => 6,
            'currency' => 'EUR',
            'unit_daily' => true,
        ]);

        Livewire::test(ListYachts::class)
            ->assertCanNotSeeTableRecords([$otherYacht])
            ->assertCanSeeTableRecords([$this->yacht]);
    }

    private function makeReservation(int $dayOffset): Reservation
    {
        return app(ReservationService::class)->request($this->yacht, [
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+905551112233',
            'date' => now()->addDays($dayOffset),
            'adults' => 4,
            'children' => 0,
        ]);
    }
}

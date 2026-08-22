<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Enums\YachtStatus;
use App\Filament\Owner\Resources\Yachts\Pages\CreateYacht;
use App\Filament\Owner\Resources\Yachts\Pages\EditYacht;
use App\Models\BlockedPeriod;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Yacht;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerPanelTest extends TestCase
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
    }

    public function test_owner_panel_pages_load(): void
    {
        $this->actingAs($this->owner);

        foreach ([
            '/yat-sahibi',
            '/yat-sahibi/yachts',
            '/yat-sahibi/yachts/create',
            "/yat-sahibi/yachts/{$this->yacht->id}/edit",
            '/yat-sahibi/reservations',
            '/yat-sahibi/blocked-periods',
            '/yat-sahibi/blocked-periods/create',
            '/yat-sahibi/calendar',
            '/yat-sahibi/earnings',
            '/yat-sahibi/profile',
        ] as $page) {
            $this->get($page)->assertSuccessful();
        }
    }

    public function test_unapproved_owner_cannot_enter_the_panel(): void
    {
        $pending = User::create([
            'name' => 'Yeni Sahip',
            'email' => 'yeni@example.com',
            'password' => Hash::make('password123'),
        ]);
        $pending->forceFill(['role' => UserRole::Owner, 'is_approved' => false])->save();

        // fresh(): create() ile olusan ornek veritabani varsayilanlarini (is_active) tasimaz
        $this->actingAs($pending->fresh())->get('/yat-sahibi')->assertForbidden();

        // Admin onayladi ama e-posta HENUZ dogrulanmadi -> dogrulama ekranina yonlenir
        $pending->forceFill(['is_approved' => true])->save();
        $this->actingAs($pending->fresh())
            ->get('/yat-sahibi')
            ->assertRedirect('/yat-sahibi/email-verification/prompt');

        // Onay + dogrulama tamam -> panel acilir
        $pending->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($pending->fresh())->get('/yat-sahibi')->assertSuccessful();
    }

    public function test_customer_and_admin_cannot_enter_the_owner_panel(): void
    {
        $admin = User::where('email', 'admin@yatkiralama.com')->firstOrFail();

        $this->actingAs($admin)->get('/yat-sahibi')->assertForbidden();
    }

    public function test_owner_only_sees_their_own_yachts_and_reservations(): void
    {
        $other = $this->makeOtherOwner();
        $otherYacht = $this->makeYachtFor($other);

        $this->actingAs($this->owner);

        $this->get('/yat-sahibi/yachts')
            ->assertSuccessful()
            ->assertSee('Mavi Rüzgar')
            ->assertDontSee('Baskasinin Yati');

        // Baskasinin kaydini adres cubugundan acmaya calismak 404 vermeli
        $this->get("/yat-sahibi/yachts/{$otherYacht->id}/edit")->assertNotFound();
    }

    public function test_owner_cannot_edit_another_owners_reservation(): void
    {
        $other = $this->makeOtherOwner();
        $otherYacht = $this->makeYachtFor($other);
        $reservation = $this->makeReservationFor($otherYacht);

        $this->actingAs($this->owner);

        $this->assertSame(
            0,
            \App\Filament\Owner\Resources\Reservations\ReservationResource::getEloquentQuery()
                ->whereKey($reservation->id)
                ->count()
        );
    }

    public function test_new_listing_starts_as_draft_and_belongs_to_the_owner(): void
    {
        $this->actingAs($this->owner);

        $port = Location::where('level', Location::LEVEL_PORT)->firstOrFail();

        Livewire::test(CreateYacht::class)
            ->fillForm([
                'name' => ['tr' => 'Yeni Tekne', 'en' => 'New Boat'],
                'slug' => 'yeni-tekne',
                'location_id' => $port->id,
                'type' => 'motoryat',
                'capacity' => 10,
                'currency' => 'EUR',
                'unit_daily' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $yacht = Yacht::where('slug', 'yeni-tekne')->firstOrFail();

        $this->assertSame($this->owner->id, $yacht->owner_id);
        $this->assertSame(YachtStatus::Draft, $yacht->status);
    }

    public function test_owner_cannot_publish_their_own_listing_through_the_form(): void
    {
        $this->actingAs($this->owner);

        $this->yacht->forceFill(['status' => YachtStatus::Draft])->save();

        Livewire::test(EditYacht::class, ['record' => $this->yacht->getKey()])
            ->fillForm(['status' => YachtStatus::Published->value])
            ->call('save');

        // Formda status alani yok; durum degismemeli
        $this->assertSame(YachtStatus::Draft, $this->yacht->refresh()->status);
    }

    public function test_owner_can_approve_a_reservation_and_the_date_closes(): void
    {
        $reservation = $this->makeReservationFor($this->yacht);

        $this->actingAs($this->owner);

        app(\App\Services\ReservationService::class)->approve($reservation, $this->owner, 'panel');

        $reservation->refresh();

        $this->assertSame(ReservationStatus::Approved, $reservation->status);
        $this->assertSame(1, BlockedPeriod::where('reservation_id', $reservation->id)->count());
    }

    public function test_calendar_marks_reserved_and_manually_blocked_days(): void
    {
        $this->actingAs($this->owner);

        BlockedPeriod::create([
            'yacht_id' => $this->yacht->id,
            'starts_at' => now()->startOfMonth()->addDays(9)->setTime(9, 0),
            'ends_at' => now()->startOfMonth()->addDays(11)->setTime(18, 0),
            'reason' => 'maintenance',
            'note' => 'Motor bakimi',
        ]);

        $page = Livewire::test(\App\Filament\Owner\Pages\Calendar::class);
        $months = $page->instance()->months();

        $blocked = collect($months[0]['days'])
            ->filter(fn ($day) => ! $day['empty'] && $day['block'] !== null);

        $this->assertGreaterThanOrEqual(2, $blocked->count());
        $this->assertSame('maintenance', $blocked->first()['block']['reason']);
    }

    public function test_earnings_only_counts_completed_reservations(): void
    {
        $completed = $this->makeReservationFor($this->yacht, 40);
        app(\App\Services\ReservationService::class)->approve($completed, $this->owner);
        $completed->forceFill([
            'status' => ReservationStatus::Completed,
            'starts_at' => now()->startOfYear()->addMonths(3),
            'ends_at' => now()->startOfYear()->addMonths(3)->addDays(3),
        ])->save();

        // Iptal edilen dokume girmemeli
        $cancelled = $this->makeReservationFor($this->yacht, 80);
        $cancelled->forceFill([
            'status' => ReservationStatus::Cancelled,
            'estimated_total' => 99999,
            'starts_at' => now()->startOfYear()->addMonths(3),
        ])->save();

        $this->actingAs($this->owner);

        $page = Livewire::test(\App\Filament\Owner\Pages\Earnings::class);
        $totals = $page->instance()->totals();

        $this->assertSame(1, $totals['count']);
        $this->assertSame((float) $completed->estimated_total, $totals['revenue']);
        $this->assertGreaterThan(0, $totals['commission']);
    }

    private function makeOtherOwner(): User
    {
        $other = User::create([
            'name' => 'Diger Sahip',
            'email' => 'diger@example.com',
            'password' => Hash::make('password123'),
        ]);

        $other->forceFill(['role' => UserRole::Owner, 'is_approved' => true])->save();

        return $other->fresh();
    }

    private function makeYachtFor(User $owner): Yacht
    {
        $yacht = Yacht::create([
            'owner_id' => $owner->id,
            'name' => ['tr' => 'Baskasinin Yati'],
            'slug' => 'baskasinin-yati',
            'type' => 'motoryat',
            'capacity' => 8,
            'currency' => 'EUR',
            'unit_daily' => true,
            'is_open' => true,
        ]);

        $yacht->forceFill(['status' => YachtStatus::Published, 'published_at' => now()])->save();
        $yacht->rates()->create(['unit' => 'day', 'price' => 1000, 'min_duration' => 1]);

        return $yacht;
    }

    private function makeReservationFor(Yacht $yacht, int $dayOffset = 25): Reservation
    {
        $start = now()->addDays($dayOffset)->setTime(10, 0);

        return app(\App\Services\ReservationService::class)->request($yacht, [
            'customer_name' => 'Test Musteri',
            'customer_email' => 'musteri@example.com',
            'customer_phone' => '+905551112233',
            'unit' => 'day',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addDays(3),
            'guests' => 4,
        ]);
    }
}

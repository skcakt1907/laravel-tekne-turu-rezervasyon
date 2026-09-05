<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\YachtStatus;
use App\Filament\Resources\Collections\Pages\ListCollections;
use App\Filament\Resources\CommissionSettings\Pages\ListCommissionSettings;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\Features\Pages\ListFeatures;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Filament\Resources\MessageLogs\Pages\ListMessageLogs;
use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Yachts\Pages\ListYachts;
use App\Models\Location;
use App\Models\User;
use App\Models\Yacht;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tablo ETKILESIMLERI: sekmeler, filtreler, siralama, arama.
 *
 * "Sayfa aciliyor mu" testi bunlari kacirir — filtreler yalnizca kullanici
 * secim yapinca calisir ve orada patlayabilir.
 */
class AdminTablesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Filament::setCurrentPanel('admin');
        Mail::fake();

        $this->actingAs(User::where('email', 'admin@yatkiralama.com')->firstOrFail());
    }

    public function test_yacht_list_tabs_work(): void
    {
        Yacht::first()->forceFill(['status' => YachtStatus::Pending])->save();

        foreach (['all', 'pending', 'published', 'draft', 'closed'] as $tab) {
            Livewire::test(ListYachts::class)
                ->set('activeTab', $tab)
                ->assertOk();
        }
    }

    public function test_yacht_filters_work(): void
    {
        $owner = User::where('email', 'sahip@yatkiralama.com')->firstOrFail();
        $port = Location::where('slug', 'bodrum')->firstOrFail();

        Livewire::test(ListYachts::class)
            ->filterTable('status', YachtStatus::Published->value)
            ->assertOk()
            ->filterTable('owner', $owner->id)
            ->assertOk()
            ->filterTable('location_id', $port->id)
            ->assertOk()
            ->filterTable('type', 'gulet')
            ->assertOk()
            ->filterTable('is_open', true)
            ->assertOk();
    }

    public function test_reservation_filters_work(): void
    {
        $owner = User::where('email', 'sahip@yatkiralama.com')->firstOrFail();

        Livewire::test(ListReservations::class)
            ->filterTable('status', [ReservationStatus::Pending->value])
            ->assertOk()
            ->filterTable('owner', $owner->id)
            ->assertOk()
            ->filterTable('needs_attention', true)
            ->assertOk()
            ->filterTable('cancel_requested', true)
            ->assertOk()
            ->filterTable('upcoming', true)
            ->assertOk();
    }

    public function test_other_admin_tables_filter_without_error(): void
    {
        Livewire::test(ListUsers::class)
            ->filterTable('role', 'owner')
            ->assertOk()
            ->filterTable('is_approved', true)
            ->assertOk();

        Livewire::test(ListLocations::class)
            ->filterTable('level', Location::LEVEL_PORT)
            ->assertOk();

        Livewire::test(ListFeatures::class)
            ->filterTable('group', 'konfor')
            ->assertOk();

        Livewire::test(ListMessageLogs::class)
            ->filterTable('channel', 'mail')
            ->assertOk()
            ->filterTable('status', 'sent')
            ->assertOk();

        Livewire::test(ListContactMessages::class)
            ->filterTable('read_at', true)
            ->assertOk();

        Livewire::test(ListCollections::class)
            ->filterTable('status', 'pending')
            ->assertOk();

        Livewire::test(ListCommissionSettings::class)
            ->filterTable('scope', 'global')
            ->assertOk();
    }

    public function test_yacht_table_search_and_sort(): void
    {
        Livewire::test(ListYachts::class)
            ->searchTable('Mavi')
            ->assertOk()
            ->assertCanSeeTableRecords(Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->get())
            ->sortTable('created_at')
            ->assertOk()
            ->sortTable('status')
            ->assertOk();
    }

    public function test_admin_record_actions_run(): void
    {
        $yacht = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();
        $yacht->forceFill(['status' => YachtStatus::Pending])->save();

        // Fotograf sarti saglanmadigi icin yayina alma REDDEDILMELI
        Livewire::test(ListYachts::class)
            ->callTableAction('publish', $yacht)
            ->assertOk();
        $this->assertSame(YachtStatus::Pending, $yacht->refresh()->status);

        // Sarti sagla, sonra yayina al
        for ($i = 0; $i < (int) config('yacht.min_photos'); $i++) {
            $yacht->photos()->create(['path' => "yachts/test-{$i}.jpg", 'sort' => $i]);
        }

        Livewire::test(ListYachts::class)
            ->callTableAction('publish', $yacht)
            ->assertOk();
        $this->assertSame(YachtStatus::Published, $yacht->refresh()->status);

        Livewire::test(ListYachts::class)
            ->callTableAction('toggleOpen', $yacht)
            ->assertOk();
        $this->assertFalse((bool) $yacht->refresh()->is_open);
    }

    public function test_admin_can_approve_a_reservation_from_the_table(): void
    {
        $yacht = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();
        $start = now()->addDays(20)->setTime(10, 0);

        $reservation = app(\App\Services\ReservationService::class)->request($yacht, [
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+905551112233',
            'unit' => 'day',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addDays(2),
            'guests' => 4,
        ]);

        Livewire::test(ListReservations::class)
            ->callTableAction('approve', $reservation)
            ->assertOk();

        $this->assertSame(ReservationStatus::Approved, $reservation->refresh()->status);
        $this->assertSame(1, $reservation->blockedPeriod()->count());
    }

    public function test_global_search_finds_yachts_reservations_and_users(): void
    {
        $yacht = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();
        $start = now()->addDays(15)->setTime(10, 0);

        $reservation = app(\App\Services\ReservationService::class)->request($yacht, [
            'customer_name' => 'Arama Testi',
            'customer_email' => 'arama@example.com',
            'customer_phone' => '+905551112233',
            'unit' => 'day',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addDays(2),
            'guests' => 4,
        ]);

        // Yat adi (ceviri JSON'unda arar)
        Livewire::test(\Filament\Livewire\GlobalSearch::class)
            ->set('search', 'Mavi')
            ->assertSee('Mavi Rüzgar');

        // Rezervasyon kodu
        Livewire::test(\Filament\Livewire\GlobalSearch::class)
            ->set('search', $reservation->code)
            ->assertSee($reservation->code);

        // Musteri adi
        Livewire::test(\Filament\Livewire\GlobalSearch::class)
            ->set('search', 'Arama Testi')
            ->assertSee('Arama Testi');

        // Kullanici
        Livewire::test(\Filament\Livewire\GlobalSearch::class)
            ->set('search', 'Demo Tur Sahibi')
            ->assertSee('Demo Tur Sahibi');
    }
}

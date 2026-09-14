<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\YachtStatus;
use App\Filament\Resources\Yachts\Pages\CreateYacht;
use App\Filament\Resources\Yachts\Pages\EditYacht;
use App\Filament\Resources\Yachts\RelationManagers\RatesRelationManager;
use App\Models\User;
use App\Models\Yacht;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@yatkiralama.com')->firstOrFail();

        Filament::setCurrentPanel('admin');
    }

    public function test_admin_panel_pages_load(): void
    {
        $this->actingAs($this->admin);

        $yacht = Yacht::firstOrFail();

        $pages = [
            '/yonetim',
            '/yonetim/yachts',
            '/yonetim/yachts/create',
            "/yonetim/yachts/{$yacht->id}/edit",
            '/yonetim/features',
            '/yonetim/users',
            '/yonetim/users/create',
            '/yonetim/reservations',
            '/yonetim/message-logs',
            '/yonetim/contact-messages',
            '/yonetim/customers',
        ];

        foreach ($pages as $page) {
            $this->get($page)->assertSuccessful();
        }
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $owner = User::where('email', 'sahip@yatkiralama.com')->firstOrFail();

        $this->actingAs($owner)->get('/yonetim')->assertForbidden();
    }

    public function test_admin_can_create_yacht_with_translations(): void
    {
        $this->actingAs($this->admin);

        $owner = User::where('role', UserRole::Owner)->firstOrFail();

        Livewire::test(CreateYacht::class)
            ->fillForm([
                'name' => ['tr' => 'Test Yatı', 'en' => 'Test Yacht'],
                'slug' => 'test-yati',
                'owner_id' => $owner->id,
                'type' => 'motoryat',
                'capacity' => 10,
                'currency' => 'EUR',
                'status' => YachtStatus::Pending->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $yacht = Yacht::where('slug', 'test-yati')->firstOrFail();

        // Çeviriler iki dilde de kaydedilmeli
        $this->assertSame('Test Yatı', $yacht->getTranslation('name', 'tr'));
        $this->assertSame('Test Yacht', $yacht->getTranslation('name', 'en'));

        // status $fillable dışında; SavesGuardedFields ile yazılmalı
        $this->assertSame(YachtStatus::Pending, $yacht->status);
    }

    public function test_edit_form_shows_both_locales(): void
    {
        $this->actingAs($this->admin);

        $yacht = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();
        $yacht->setTranslation('name', 'en', 'Blue Wind')->save();

        Livewire::test(EditYacht::class, ['record' => $yacht->getKey()])
            ->assertFormSet([
                'name' => ['tr' => 'Mavi Rüzgar', 'en' => 'Blue Wind'],
            ]);
    }

    public function test_owner_approval_toggle_is_guarded_but_settable(): void
    {
        $this->actingAs($this->admin);

        $owner = User::create([
            'name' => 'Yeni Sahip',
            'email' => 'yeni@example.com',
            'password' => 'password123',
        ]);

        // Mass-assignment ile role/is_approved yazılamamalı
        $owner->fill(['role' => UserRole::Admin->value, 'is_approved' => true]);
        $this->assertNotSame(UserRole::Admin, $owner->role);
        $this->assertFalse((bool) $owner->is_approved);
    }

    public function test_rates_relation_manager_creates_season_price(): void
    {
        $this->actingAs($this->admin);

        $yacht = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();

        Livewire::test(RatesRelationManager::class, [
            'ownerRecord' => $yacht,
            'pageClass' => EditYacht::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'label' => 'Yuksek sezon',
                'season_start' => '2026-06-15',
                'season_end' => '2026-09-15',
                'price' => 3600,
            ])
            ->assertHasNoActionErrors();

        $rate = $yacht->rates()->where('label', 'Yuksek sezon')->firstOrFail();
        $this->assertSame('3600.00', $rate->price);
        $this->assertFalse($rate->isBase());

        // Sezon fiyati temel fiyati ezmeli (dar aralik kazanir)
        $resolved = app(\App\Services\PricingService::class)
            ->resolveRate($yacht->fresh(), \Illuminate\Support\Carbon::parse('2026-07-01'));
        $this->assertSame($rate->id, $resolved->id);
    }
}

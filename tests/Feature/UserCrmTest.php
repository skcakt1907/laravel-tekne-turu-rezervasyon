<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\RelationManagers\CustomerNotesRelationManager;
use App\Filament\Resources\Users\RelationManagers\ReservationsRelationManager;
use App\Models\CustomerNote;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Kullanıcı detayındaki CRM sekmeleri: Notlar + Rezervasyonlar. */
class UserCrmTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Filament::setCurrentPanel('admin');

        $this->admin = User::where('email', 'admin@yatkiralama.com')->firstOrFail();
        $this->targetUser = User::where('email', 'sahip@yatkiralama.com')->firstOrFail();

        $this->actingAs($this->admin);
    }

    public function test_edit_user_page_shows_crm_tabs(): void
    {
        Livewire::test(EditUser::class, ['record' => $this->targetUser->getKey()])
            ->assertSuccessful()
            ->assertSee('Notlar')
            ->assertSee('Rezervasyonlar');
    }

    public function test_admin_can_add_a_customer_note(): void
    {
        Livewire::test(CustomerNotesRelationManager::class, [
            'ownerRecord' => $this->targetUser,
            'pageClass' => EditUser::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'Telefon görüşmesi',
                'body' => 'Müşteri fiyat sordu, teklif gönderildi.',
            ])
            ->assertHasNoActionErrors();

        $note = CustomerNote::where('user_id', $this->targetUser->id)->firstOrFail();

        $this->assertSame('Telefon görüşmesi', $note->title);
        $this->assertSame($this->admin->id, $note->admin_id);
    }

    public function test_reservations_relation_manager_lists_customer_bookings(): void
    {
        Livewire::test(ReservationsRelationManager::class, [
            'ownerRecord' => $this->targetUser,
            'pageClass' => EditUser::class,
        ])->assertSuccessful();
    }
}

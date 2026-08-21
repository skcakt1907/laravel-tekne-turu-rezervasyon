<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Mail\ReservationMail;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Yacht;
use App\Services\ReservationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private Yacht $yacht;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Mail::fake();

        $this->yacht = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();
    }

    public function test_membership_is_optional_for_booking(): void
    {
        $reservation = $this->makeReservation();

        $this->assertNull($reservation->user_id);
        $this->assertSame(ReservationStatus::Pending, $reservation->status);
    }

    public function test_registration_links_past_reservations_by_email(): void
    {
        $reservation = $this->makeReservation();

        $this->post('/kayit', [
            'name' => 'Test Musteri',
            'email' => 'test@example.com',
            'phone' => '+905551112233',
            'password' => 'sifre12345',
            'password_confirmation' => 'sifre12345',
        ])->assertRedirect();

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame($user->id, $reservation->refresh()->user_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_account_page_lists_only_own_reservations(): void
    {
        $mine = $this->makeReservation('benim@example.com');
        $other = $this->makeReservation('baskasi@example.com', 60);

        $user = $this->makeCustomer('benim@example.com');

        // Onceki talebin flash mesaji kodu tasiyor; sayfayi degil oturumu kirletiyor
        $this->flushSession();

        $this->actingAs($user)
            ->get('/hesabim')
            ->assertOk()
            ->assertSee($mine->code)
            ->assertDontSee($other->code);
    }

    public function test_guests_are_redirected_from_account_pages(): void
    {
        $this->get('/hesabim')->assertRedirect();
        $this->get('/hesabim/profil')->assertRedirect();
    }

    public function test_login_and_logout(): void
    {
        $user = $this->makeCustomer('giris@example.com');

        $this->post('/giris', ['email' => 'giris@example.com', 'password' => 'sifre12345'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);

        $this->post('/cikis')->assertRedirect();
        $this->assertGuest();

        $this->post('/giris', ['email' => 'giris@example.com', 'password' => 'yanlis'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_profile_update_requires_current_password_for_a_new_one(): void
    {
        $user = $this->makeCustomer('profil@example.com');
        $this->actingAs($user);

        $this->post('/hesabim/profil', [
            'name' => 'Yeni Ad',
            'password' => 'yenisifre123',
            'password_confirmation' => 'yenisifre123',
            'current_password' => 'yanlis-sifre',
        ])->assertSessionHasErrors('current_password');

        $this->post('/hesabim/profil', [
            'name' => 'Yeni Ad',
            'phone' => '+905559998877',
            'password' => 'yenisifre123',
            'password_confirmation' => 'yenisifre123',
            'current_password' => 'sifre12345',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Yeni Ad', $user->name);
        $this->assertTrue(Hash::check('yenisifre123', $user->password));
    }

    public function test_customer_can_request_cancellation_but_cannot_cancel(): void
    {
        $reservation = app(ReservationService::class)->approve($this->makeReservation());

        $this->post("/rezervasyon/{$reservation->code}/iptal-talebi", [
            'token' => $reservation->access_token,
            'reason' => 'Planlarim degisti',
        ])->assertRedirect();

        $reservation->refresh();

        // Talep kaydedilir ama rezervasyon HÂLÂ onayli - karari yat sahibi verir
        $this->assertNotNull($reservation->cancel_requested_at);
        $this->assertSame(ReservationStatus::Approved, $reservation->status);
        $this->assertSame(1, $reservation->blockedPeriod()->count());

        // Yat sahibine ve admin'e bildirim gider
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->template === 'cancel_requested_owner');
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->template === 'cancel_requested_admin');

        // Denetim izine dusmeli
        $this->assertSame(1, $reservation->logs()->where('action', 'cancel_requested')->count());
    }

    public function test_cancellation_request_needs_a_valid_token(): void
    {
        $reservation = $this->makeReservation();

        $this->post("/rezervasyon/{$reservation->code}/iptal-talebi", [
            'token' => 'yanlis-token',
            'reason' => 'Deneme',
        ])->assertForbidden();

        $this->assertNull($reservation->refresh()->cancel_requested_at);
    }

    public function test_cancellation_cannot_be_requested_twice(): void
    {
        $reservation = $this->makeReservation();

        $payload = ['token' => $reservation->access_token, 'reason' => 'Ilk talep'];

        $this->post("/rezervasyon/{$reservation->code}/iptal-talebi", $payload)->assertRedirect();
        $this->post("/rezervasyon/{$reservation->code}/iptal-talebi", $payload)->assertStatus(410);

        $this->assertSame(1, $reservation->refresh()->logs()->where('action', 'cancel_requested')->count());
    }

    public function test_owner_accepting_the_request_cancels_and_frees_the_dates(): void
    {
        $reservation = app(ReservationService::class)->approve($this->makeReservation());

        $this->post("/rezervasyon/{$reservation->code}/iptal-talebi", [
            'token' => $reservation->access_token,
            'reason' => 'Hava kotu',
        ]);

        $owner = User::where('email', 'sahip@yatkiralama.com')->firstOrFail();
        app(ReservationService::class)->cancel($reservation->refresh(), 'Musteri talebi', $owner, 'panel');

        $reservation->refresh();

        $this->assertSame(ReservationStatus::Cancelled, $reservation->status);
        $this->assertSame(0, $reservation->blockedPeriod()->count());
    }

    private function makeCustomer(string $email): User
    {
        $user = User::create([
            'name' => 'Musteri',
            'email' => $email,
            'password' => Hash::make('sifre12345'),
        ]);

        return $user->fresh();
    }

    private function makeReservation(string $email = 'test@example.com', int $dayOffset = 30): Reservation
    {
        $start = now()->addDays($dayOffset)->setTime(10, 0);

        $this->post('/rezervasyon-talebi', [
            'yacht_id' => $this->yacht->id,
            'unit' => 'day',
            'starts_at' => $start->format('Y-m-d H:i'),
            'ends_at' => $start->copy()->addDays(3)->format('Y-m-d H:i'),
            'guests' => 4,
            'customer_name' => 'Test Musteri',
            'customer_email' => $email,
            'customer_phone' => '+905551112233',
            'kvkk' => '1',
            'whatsapp_consent' => '1',
        ]);

        return Reservation::where('customer_email', $email)->latest('id')->firstOrFail();
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Mail\ReservationMail;
use App\Models\MessageLog;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Yacht;
use App\Services\ReservationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private Yacht $yacht;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->yacht = Yacht::where('slug', 'demo-gulet-mavi-ruzgar')->firstOrFail();
    }

    public function test_request_notifies_customer_and_admin(): void
    {
        Mail::fake();

        $reservation = $this->makeReservation();

        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->template === 'request_received_customer'
            && $mail->hasTo('test@example.com'));
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->template === 'request_new_admin'
            && $mail->hasTo('admin@yatkiralama.com'));

        // Tur sahibi diye ayri bir taraf kalmadi: musteri + yonetim, iki mail
        $logs = MessageLog::where('related_id', $reservation->id)->get();
        $this->assertCount(2, $logs);
        $this->assertTrue($logs->every(fn (MessageLog $log) => $log->channel === 'mail' && $log->status === 'sent'));
    }

    public function test_approval_and_rejection_notify_the_right_people(): void
    {
        Mail::fake();
        $service = app(ReservationService::class);

        $service->approve($this->makeReservation());

        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->template === 'approved_customer');
        // Onayi zaten yonetim veriyor; kendine ayrica mail gitmiyor
        Mail::assertNotSent(ReservationMail::class, fn ($mail) => $mail->template === 'approved_owner');

        $service->reject($this->makeReservation('ikinci@example.com', 60), 'Yat bakimda');

        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->template === 'rejected_customer'
            && $mail->hasTo('ikinci@example.com'));
    }

    public function test_customer_mail_uses_customer_locale(): void
    {
        Mail::fake();

        $this->post('/en/rezervasyon-talebi', $this->payload('ingiliz@example.com'));

        $reservation = Reservation::firstOrFail();
        $this->assertSame('en', $reservation->customer_locale);

        $log = MessageLog::where('template', 'request_received_customer')->firstOrFail();
        $this->assertSame('en', $log->locale);
    }

    public function test_failed_send_is_logged_without_breaking_the_flow(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP kapali'));

        $reservation = $this->makeReservation();

        // Rezervasyon yine de olusmali
        $this->assertSame(ReservationStatus::Pending, $reservation->status);

        $failed = MessageLog::where('status', 'failed')->get();
        $this->assertGreaterThan(0, $failed->count());
        $this->assertSame('SMTP kapali', $failed->first()->error);
    }

    public function test_reminder_escalation_and_completion_command(): void
    {
        Mail::fake();

        $reservation = $this->makeReservation();
        $reservation->forceFill(['created_at' => now()->subHours(5)])->save();

        $this->artisan('reservations:process', ['--only' => 'remind'])->assertSuccessful();

        $reservation->refresh();
        $this->assertNotNull($reservation->reminded_at);
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->template === 'pending_reminder_admin');

        // Ikinci calistirmada tekrar gondermemeli
        $this->artisan('reservations:process', ['--only' => 'remind'])->assertSuccessful();
        $this->assertSame(1, MessageLog::where('template', 'pending_reminder_admin')->count());

        // 12 saat -> admin devralir
        $reservation->forceFill(['created_at' => now()->subHours(13)])->save();
        $this->artisan('reservations:process', ['--only' => 'escalate'])->assertSuccessful();

        $reservation->refresh();
        $this->assertNotNull($reservation->escalated_at);
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->template === 'escalated_admin');
    }

    public function test_past_reservations_are_completed_automatically(): void
    {
        Mail::fake();

        $reservation = app(ReservationService::class)->approve($this->makeReservation());
        $reservation->forceFill([
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDay(),
        ])->save();

        $this->artisan('reservations:process', ['--only' => 'complete'])->assertSuccessful();

        $reservation->refresh();
        $this->assertSame(ReservationStatus::Completed, $reservation->status);
        $this->assertNotNull($reservation->completed_at);

        // Komisyon onay aninda dondurulmustu, tamamlanmada degismemeli
    }

    public function test_trip_reminder_is_sent_once(): void
    {
        Mail::fake();

        $reservation = app(ReservationService::class)->approve($this->makeReservation());
        $reservation->forceFill([
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(5),
        ])->save();

        $this->artisan('reservations:process', ['--only' => 'trip'])->assertSuccessful();
        $this->artisan('reservations:process', ['--only' => 'trip'])->assertSuccessful();

        $this->assertSame(1, MessageLog::where('template', 'trip_reminder_customer')->count());
    }

    public function test_admin_can_approve_from_panel(): void
    {
        Mail::fake();

        $admin = User::where('email', 'admin@yatkiralama.com')->firstOrFail();
        $reservation = $this->makeReservation();

        $this->actingAs($admin);
        app(ReservationService::class)->approve($reservation, $admin, 'panel');

        $reservation->refresh();
        $this->assertSame(ReservationStatus::Approved, $reservation->status);

        $log = $reservation->logs()->where('action', 'approved')->firstOrFail();
        $this->assertSame('panel', $log->channel);
        $this->assertSame($admin->id, $log->user_id);
    }

    private function makeReservation(string $email = 'test@example.com', int $dayOffset = 30): Reservation
    {
        $this->post('/rezervasyon-talebi', $this->payload($email, $dayOffset));

        return Reservation::latest('id')->firstOrFail();
    }

    private function payload(string $email, int $dayOffset = 30): array
    {
        return [
            'yacht_id' => $this->yacht->id,
            'date' => now()->addDays($dayOffset)->toDateString(),
            'adults' => 6,
            'children' => 0,
            'customer_name' => 'Test Musteri',
            'customer_email' => $email,
            'customer_phone' => '+905551112233',
            'kvkk' => '1',
            'whatsapp_consent' => '1',
        ];
    }
}

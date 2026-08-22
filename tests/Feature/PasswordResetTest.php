<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->customer = User::create([
            'name' => 'Test Musteri',
            'email' => 'musteri@example.com',
            'password' => Hash::make('eskisifre123'),
        ]);
    }

    public function test_reset_pages_load(): void
    {
        $this->get('/sifremi-unuttum')->assertOk()->assertSee('Şifre Sıfırlama');
        $this->get('/sifre-sifirla/ornek-token')->assertOk();
        $this->get('/giris')->assertOk()->assertSee('Şifremi unuttum');
    }

    public function test_reset_link_is_sent_in_turkish_with_a_site_url(): void
    {
        Notification::fake();

        $this->post('/sifremi-unuttum', ['email' => 'musteri@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($this->customer, ResetPassword::class, function ($notification) {
            $mail = $notification->toMail($this->customer);

            // Panel degil SITE adresi olmali
            $this->assertStringContainsString('/sifre-sifirla/', $mail->actionUrl);
            $this->assertStringNotContainsString('/yonetim', $mail->actionUrl);

            return str_contains($mail->subject, 'şifre sıfırlama');
        });
    }

    public function test_unknown_email_does_not_reveal_whether_it_exists(): void
    {
        Notification::fake();

        $this->post('/sifremi-unuttum', ['email' => 'yok@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_customer_can_reset_and_sign_in_with_the_new_password(): void
    {
        $token = \Illuminate\Support\Facades\Password::createToken($this->customer);

        $this->post('/sifre-sifirla', [
            'token' => $token,
            'email' => 'musteri@example.com',
            'password' => 'yenisifre123',
            'password_confirmation' => 'yenisifre123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('yenisifre123', $this->customer->refresh()->password));

        $this->post('/giris', ['email' => 'musteri@example.com', 'password' => 'yenisifre123'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($this->customer);
    }

    public function test_a_wrong_token_is_rejected(): void
    {
        $this->post('/sifre-sifirla', [
            'token' => 'gecersiz-token',
            'email' => 'musteri@example.com',
            'password' => 'yenisifre123',
            'password_confirmation' => 'yenisifre123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('eskisifre123', $this->customer->refresh()->password));
    }
}

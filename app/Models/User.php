<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'whatsapp_no', 'locale',
        'company_name', 'tax_office', 'tax_number', 'iban', 'address',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_approved' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Tek panel kaldi: tur sahibi paneli kaldirildi.
        return $panel->getId() === 'admin' && $this->isAdmin();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function yachts(): HasMany
    {
        return $this->hasMany(Yacht::class, 'owner_id');
    }

    public function ownerReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'owner_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'user_id');
    }

    /** CRM notları — bu kullanıcı müşteri olarak görüntülenirken. */
    public function customerNotes(): HasMany
    {
        return $this->hasMany(CustomerNote::class, 'user_id')->latest();
    }

    public function notificationPhone(): ?string
    {
        return $this->whatsapp_no ?: $this->phone;
    }
}

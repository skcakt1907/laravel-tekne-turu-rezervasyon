<?php

namespace App\Models;

use App\Enums\RentalUnit;
use App\Enums\ReservationStatus;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Reservation extends Model
{
    /**
     * Korumalı alanlar bilerek dışarıda: status, code, komisyon alanları, access_token,
     * owner_id ve tutarlar yalnızca sunucu tarafında (ReservationService) yazılır.
     */
    protected $fillable = [
        'yacht_id', 'user_id', 'customer_name', 'customer_email', 'customer_phone',
        'customer_whatsapp', 'customer_locale', 'unit', 'starts_at', 'ends_at',
        'adults', 'children', 'message',
    ];

    protected $casts = [
        'status' => ReservationStatus::class,
        'unit' => RentalUnit::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'base_amount' => 'decimal:2',
        'extras_amount' => 'decimal:2',
        'estimated_total' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'price_breakdown' => 'array',
        'selected_extras' => 'array',
        'responded_at' => 'datetime',
        'reminded_at' => 'datetime',
        'escalated_at' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancel_requested_at' => 'datetime',
    ];

    /** CRM kaydi -- uyelik yok, musteri bu tabloda tutulur */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function yacht(): BelongsTo
    {
        return $this->belongsTo(Yacht::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ReservationLog::class)->latest();
    }

    public function blockedPeriod(): HasMany
    {
        return $this->hasMany(BlockedPeriod::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MessageLog::class, 'related_id')
            ->where('related_type', self::class);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', ReservationStatus::Pending);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereIn('status', [ReservationStatus::Approved, ReservationStatus::Completed]);
    }

    /** Verilen aralıkla çakışan rezervasyonlar: starts_at < :bitis AND ends_at > :baslangic */
    public function scopeOverlapping(Builder $q, $start, $end): Builder
    {
        return $q->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }

    public static function generateCode(): string
    {
        do {
            $code = 'YK-'.now()->format('y').'-'.strtoupper(Str::random(5));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /** Toplam kişi sayısı (yetişkin + çocuk) — mesaj/şablon/ek ücret hesaplarında kullanılır. */
    public function getGuestsAttribute(): int
    {
        return $this->adults + $this->children;
    }

    public function durationHours(): float
    {
        return $this->starts_at->diffInMinutes($this->ends_at) / 60;
    }

    public function durationDays(): float
    {
        return $this->durationHours() / 24;
    }

    public function isPending(): bool
    {
        return $this->status === ReservationStatus::Pending;
    }

    /** Müşteri iptal talebi bekliyor mu? */
    public function hasCancelRequest(): bool
    {
        return $this->cancel_requested_at !== null
            && in_array($this->status, [ReservationStatus::Pending, ReservationStatus::Approved], true);
    }

    /** Müşteri bu rezervasyon için iptal talebi gönderebilir mi? */
    public function canRequestCancellation(): bool
    {
        return $this->cancel_requested_at === null
            && in_array($this->status, [ReservationStatus::Pending, ReservationStatus::Approved], true);
    }

    /** Bildirimdeki güvenli bağlantı. */
    public function accessUrl(): string
    {
        return route('reservation.show', ['code' => $this->code, 'token' => $this->access_token]);
    }

    /** Rezervasyon sayfasına giden QR kod — SVG data URI, harici istek yok. */
    public function qrCodeDataUri(): string
    {
        $qrCode = new QrCode(data: $this->accessUrl(), size: 220, margin: 8);

        return (new SvgWriter)->write($qrCode)->getDataUri();
    }
}

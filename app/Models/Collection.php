<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    protected $fillable = [
        'owner_id', 'year', 'month', 'revenue', 'commission', 'currency',
        'reservation_count', 'status', 'collected_at', 'invoice_no', 'note',
    ];

    protected $casts = [
        'revenue' => 'decimal:2',
        'commission' => 'decimal:2',
        'collected_at' => 'date',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function periodLabel(): string
    {
        return str_pad((string) $this->month, 2, '0', STR_PAD_LEFT).'/'.$this->year;
    }
}

<?php

namespace App\Models;

use App\Enums\RentalUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YachtRate extends Model
{
    protected $fillable = [
        'yacht_id', 'unit', 'label', 'season_start', 'season_end', 'price', 'min_duration',
    ];

    protected $casts = [
        'unit' => RentalUnit::class,
        'season_start' => 'date',
        'season_end' => 'date',
        'price' => 'decimal:2',
    ];

    public function yacht(): BelongsTo
    {
        return $this->belongsTo(Yacht::class);
    }

    public function isBase(): bool
    {
        return $this->season_start === null || $this->season_end === null;
    }

    /** Sezon genişliği gün cinsinden — dar aralık geniş aralığı ezer. */
    public function spanDays(): int
    {
        if ($this->isBase()) {
            return PHP_INT_MAX;
        }

        return (int) $this->season_start->diffInDays($this->season_end) + 1;
    }
}

<?php

namespace App\Models;

use App\Enums\YachtStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Yacht extends Model
{
    use HasTranslations, SoftDeletes;

    public array $translatable = ['name', 'description', 'rules'];

    protected $fillable = [
        'owner_id', 'name', 'slug', 'description', 'rules',
        'type', 'brand', 'model', 'build_year', 'length_m', 'cabins', 'beds', 'wc',
        'capacity', 'sleep_capacity', 'engine', 'with_crew',
        'unit_hourly', 'unit_daily', 'unit_weekly', 'currency',
        'turnaround_minutes', 'day_start', 'day_end', 'checkin_time', 'checkout_time',
        'weekly_start_dow', 'is_open',
    ];

    protected $casts = [
        'status' => YachtStatus::class,
        'with_crew' => 'boolean',
        'unit_hourly' => 'boolean',
        'unit_daily' => 'boolean',
        'unit_weekly' => 'boolean',
        'is_open' => 'boolean',
        'is_featured' => 'boolean',
        'length_m' => 'decimal:2',
        'price_from' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(YachtPhoto::class)->orderBy('sort');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(YachtRate::class);
    }

    public function extras(): HasMany
    {
        return $this->hasMany(YachtExtra::class)->orderBy('sort');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function blockedPeriods(): HasMany
    {
        return $this->hasMany(BlockedPeriod::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', YachtStatus::Published);
    }

    public function scopeBookable(Builder $q): Builder
    {
        return $q->published()->where('is_open', true);
    }

    public function coverUrl(): ?string
    {
        $cover = $this->photos->firstWhere('is_cover', true) ?? $this->photos->first();

        return $cover?->url();
    }
}

<?php

namespace App\Models;

use App\Enums\ExtraCalculation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class YachtExtra extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = ['yacht_id', 'name', 'amount', 'calculation', 'is_required', 'sort'];

    protected $casts = [
        'calculation' => ExtraCalculation::class,
        'amount' => 'decimal:2',
        'is_required' => 'boolean',
    ];

    public function yacht(): BelongsTo
    {
        return $this->belongsTo(Yacht::class);
    }

    public function calculate(int $guests, float $days): float
    {
        return match ($this->calculation) {
            ExtraCalculation::Fixed => (float) $this->amount,
            ExtraCalculation::PerPerson => (float) $this->amount * $guests,
            ExtraCalculation::PerDay => (float) $this->amount * max(1, ceil($days)),
        };
    }
}

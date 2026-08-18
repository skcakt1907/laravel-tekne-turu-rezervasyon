<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Location extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description', 'seo_title', 'seo_description'];

    protected $fillable = [
        'parent_id', 'level', 'name', 'slug', 'description', 'seo_title', 'seo_description',
        'cover', 'lat', 'lng', 'sort', 'is_active', 'is_featured',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public const LEVEL_COUNTRY = 1;
    public const LEVEL_REGION = 2;
    public const LEVEL_PORT = 3;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function yachts(): HasMany
    {
        return $this->hasMany(Yacht::class);
    }

    /** Bu konum ve altındaki tüm konum id'leri (3 seviye, düz sorgu). */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $level2 = self::where('parent_id', $this->id)->pluck('id')->all();
        $ids = array_merge($ids, $level2);

        if ($level2) {
            $ids = array_merge($ids, self::whereIn('parent_id', $level2)->pluck('id')->all());
        }

        return $ids;
    }
}

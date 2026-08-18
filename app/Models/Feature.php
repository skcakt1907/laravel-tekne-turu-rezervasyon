<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Feature extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = ['name', 'slug', 'group', 'icon', 'sort', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function yachts(): BelongsToMany
    {
        return $this->belongsToMany(Yacht::class);
    }
}

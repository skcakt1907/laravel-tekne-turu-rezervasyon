<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class YachtPhoto extends Model
{
    use HasTranslations;

    public array $translatable = ['alt'];

    protected $fillable = ['yacht_id', 'path', 'alt', 'sort', 'is_cover'];

    protected $casts = ['is_cover' => 'boolean'];

    public function yacht(): BelongsTo
    {
        return $this->belongsTo(Yacht::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

/**
 * Tur galerisindeki tek bir medya: fotograf ya da video.
 * Video ise `poster` alaninda kucuk resim (kapak karesi) tutulur.
 */
class YachtPhoto extends Model
{
    use HasTranslations;

    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO = 'video';

    public array $translatable = ['alt'];

    protected $fillable = ['yacht_id', 'path', 'type', 'poster', 'alt', 'sort', 'is_cover'];

    protected $casts = ['is_cover' => 'boolean'];

    /**
     * Tur dosya uzantisindan belirlenir — panelden, seeder'dan, nereden
     * kaydedilirse kaydedilsin tutarli kalsin diye model seviyesinde.
     */
    protected static function booted(): void
    {
        static::saving(function (self $media) {
            $media->type = str_ends_with(mb_strtolower((string) $media->path), '.mp4')
                ? self::TYPE_VIDEO
                : self::TYPE_IMAGE;

            if ($media->isVideo()) {
                $media->is_cover = false; // video kart gorseli olamaz
            } else {
                $media->poster = null;    // fotografin kapak karesi olmaz
            }
        });
    }

    public function yacht(): BelongsTo
    {
        return $this->belongsTo(Yacht::class);
    }

    public function scopeImages(Builder $q): Builder
    {
        return $q->where('type', self::TYPE_IMAGE);
    }

    public function isVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    public function url(): string
    {
        return Storage::disk('uploads')->url($this->path);
    }

    /** Listede/galeride gosterilecek kucuk resim — videoda kapak karesi. */
    public function thumbnailUrl(): ?string
    {
        if (! $this->isVideo()) {
            return $this->url();
        }

        return $this->poster ? Storage::disk('uploads')->url($this->poster) : null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasTranslations;

    public array $translatable = ['title', 'body', 'seo_title', 'seo_description'];

    protected $fillable = ['slug', 'title', 'body', 'seo_title', 'seo_description', 'is_active', 'sort'];

    protected $casts = ['is_active' => 'boolean'];
}

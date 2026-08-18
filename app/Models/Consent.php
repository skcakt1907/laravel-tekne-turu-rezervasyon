<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Consent extends Model
{
    protected $fillable = [
        'subject_type', 'subject_id', 'email', 'phone', 'type',
        'text_version', 'granted', 'ip', 'user_agent',
    ];

    protected $casts = ['granted' => 'boolean'];

    public const TYPE_KVKK = 'kvkk';
    public const TYPE_WHATSAPP = 'whatsapp';
    public const TYPE_TERMS = 'terms';

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}

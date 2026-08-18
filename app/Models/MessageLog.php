<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MessageLog extends Model
{
    protected $fillable = [
        'related_type', 'related_id', 'channel', 'template', 'locale', 'recipient',
        'user_id', 'status', 'provider_message_id', 'payload', 'error',
        'sent_at', 'delivered_at', 'read_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}

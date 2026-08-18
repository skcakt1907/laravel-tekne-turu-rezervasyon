<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    protected $fillable = ['scope', 'target_id', 'rate', 'effective_from', 'note'];

    protected $casts = [
        'rate' => 'decimal:2',
        'effective_from' => 'date',
    ];

    public const SCOPE_GLOBAL = 'global';
    public const SCOPE_OWNER = 'owner';
    public const SCOPE_YACHT = 'yacht';
}

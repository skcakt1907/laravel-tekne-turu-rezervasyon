<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerNote extends Model
{
    /**
     * admin_id formda gösterilmez, RelationManager mutateFormDataUsing ile
     * sunucu tarafından set edilir (Yacht.owner_id ile aynı desen).
     */
    protected $fillable = ['user_id', 'admin_id', 'title', 'body'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}

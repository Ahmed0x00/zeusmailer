<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerifyResult extends Model
{
    protected $fillable = [
        'batch_id', 'email', 'status', 'reason', 'mx', 'response', 'checked_at'
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(VerifyBatch::class, 'batch_id');
    }
}

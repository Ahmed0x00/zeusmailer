<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerifyBatch extends Model
{
    protected $fillable = [
        'id', 'filename', 'total', 'processed', 'status'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function results(): HasMany
    {
        return $this->hasMany(VerifyResult::class, 'batch_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebugLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'email',
        'smtp_username',
        'status',
        'debug',
    ];

    protected $casts = [
        'debug' => 'string',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
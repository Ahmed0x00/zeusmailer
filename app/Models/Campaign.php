<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'html_body',
        'smtp_input',
        'total_emails',
        'delay',
        'from_name',
        'debug_log',
        'sent',
        'failed',
        'completed',
        'log',
    ];

    protected $casts = [
        'log' => 'array',
        'completed' => 'boolean',
    ];
}
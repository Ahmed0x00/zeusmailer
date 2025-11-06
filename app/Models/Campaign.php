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
        'from_name',
        'total_emails',
        'sent',
        'failed',
        'delay',
        'log',
        'debug_log',
        'status',           // ← NEW
        'sent_emails',      // ← NEW
        'all_emails',       // ← NEW
        'completed',        // ← NEW
        'failed_emails',    // ← NEW
        'current_index',    // ← NEW
        'processed_emails', // ← NEW
        'queue_name',
    ];

    protected $casts = [
        'log' => 'array',
        'sent_emails' => 'array',  // ← NEW: JSON → PHP array
        'all_emails' => 'array',   // ← NEW: JSON → PHP array
        'completed' => 'boolean',
        'failed_emails' => 'array', // ← NEW: JSON → PHP array
        'processed_emails' => 'array', // ← NEW: JSON → PHP array
    ];
}
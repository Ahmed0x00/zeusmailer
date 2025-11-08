<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmtpResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'password',
        'smtp_host',
        'port',
        'provider',
        'status',
        'response_time',
    ];

    public function batch()
{
    return $this->belongsTo(SmtpBatch::class, 'batch_id');
}

}


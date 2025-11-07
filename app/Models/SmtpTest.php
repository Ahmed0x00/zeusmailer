<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmtpTest extends Model
{
    use HasFactory;

    protected $fillable = [
    'batch_id',
    'host',
    'port',
    'username',
    'password',
    'from_email',
    'from_name',
    'subject',
    'html_body',
    'status',
    'message',
    'debug',
];

}

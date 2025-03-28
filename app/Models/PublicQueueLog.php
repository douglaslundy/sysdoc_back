<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicQueueLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ip_address',
        'user_agent',
        'host_name',
        'referer',
        'accessed_at',
    ];
}

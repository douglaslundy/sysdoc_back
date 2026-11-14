<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicQueueLog extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;
    public $timestamps = false;

    protected $fillable = [
        'secretaria_id',
        'ip_address',
        'user_agent',
        'host_name',
        'referer',
        'accessed_at',
        'ip_origin',
        'client_info',
    ];

    protected $casts = ['client_info' => 'array'];
}

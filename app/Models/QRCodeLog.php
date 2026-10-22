<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QRCodeLog extends Model
{
    protected $table = 'qrcode_logs';

    protected $fillable = [
        'uuid',
        'queue_id',
        'host_name',
        'position',
        'ip_address',
        'user_agent',
        'location',
        'referer',
        'accessed_at',
        'ip_origin',
        'client_info',
    ];

    protected $casts = ['client_info' => 'array'];

    public function queue()
    {
        return $this->belongsTo(Queue::class, 'queue_id');
    }
}

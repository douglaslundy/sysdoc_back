<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    ];

    public function queue()
    {
        return $this->belongsTo(Queue::class, 'queue_id');
    }

}
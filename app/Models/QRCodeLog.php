<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QRCodeLog extends Model
{
    // Herda a secretaria do pai (sem coluna própria).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\SecretariaDoPaiScope('queue_id', 'queue'));
    }

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

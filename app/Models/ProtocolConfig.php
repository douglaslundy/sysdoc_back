<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProtocolConfig extends Model
{
    protected $table = 'protocol_configs';

    protected $fillable = [
        'allow_external_protocols',
        'allow_reopen',
        'notify_internal',
        'notify_email',
        'notify_whatsapp',
        'default_priority',
        'default_due_days',
        'observacoes',
    ];

    protected $casts = [
        'allow_external_protocols' => 'boolean',
        'allow_reopen' => 'boolean',
        'notify_internal' => 'boolean',
        'notify_email' => 'boolean',
        'notify_whatsapp' => 'boolean',
        'default_due_days' => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'allow_external_protocols' => true,
            'allow_reopen' => true,
            'notify_internal' => true,
            'notify_email' => false,
            'notify_whatsapp' => false,
            'default_priority' => 'normal',
            'default_due_days' => 5,
        ]);
    }
}

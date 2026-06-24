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
        'evolution_base_url',
        'evolution_api_key',
        'evolution_default_session',
        'evolution_enabled',
        'observacoes',
        'whatsapp_base_url',
        'whatsapp_api_key',
        'whatsapp_instance_name',
        'whatsapp_instance_token',
        'whatsapp_ativo',
    ];

    protected $casts = [
        'allow_external_protocols' => 'boolean',
        'allow_reopen' => 'boolean',
        'notify_internal' => 'boolean',
        'notify_email' => 'boolean',
        'notify_whatsapp' => 'boolean',
        'default_due_days' => 'integer',
        'evolution_enabled' => 'boolean',
        'whatsapp_ativo' => 'boolean',
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
            'evolution_enabled' => false,
            'whatsapp_ativo' => false,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProtocolConfig extends Model
{
    protected $table = 'protocol_configs';

    protected $fillable = [
        'allow_external_protocols',
        'allow_reopen',
        'default_priority',
        'default_due_days',
        'observacoes',
    ];

    protected $casts = [
        'allow_external_protocols' => 'boolean',
        'allow_reopen' => 'boolean',
        'default_due_days' => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'allow_external_protocols' => true,
            'allow_reopen' => true,
            'default_priority' => 'normal',
            'default_due_days' => 5,
        ]);
    }
}

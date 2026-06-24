<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationChannelConfig extends Model
{
    protected $table = 'notification_channel_configs';

    protected $fillable = [
        'canal',
        'ativo',
        'configuracao',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'configuracao' => 'array',
    ];

    public static function current(string $channel): self
    {
        return static::query()->firstOrCreate(
            ['canal' => $channel],
            ['ativo' => false, 'configuracao' => []]
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatRealtimeConfig extends Model
{
    protected $fillable = [
        'engine', 'active', 'app_id', 'app_key', 'app_secret', 'cluster',
        'host', 'port', 'scheme', 'use_tls', 'updated_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'use_tls' => 'boolean',
        'port' => 'integer',
        'app_id' => 'encrypted',
        'app_key' => 'encrypted',
        'app_secret' => 'encrypted',
    ];

    protected $hidden = ['app_id', 'app_key', 'app_secret'];

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'engine' => env('PUSHER_HOST') ? 'soketi' : 'pusher',
                'active' => filled(env('PUSHER_APP_KEY')),
                'app_id' => env('PUSHER_APP_ID'),
                'app_key' => env('PUSHER_APP_KEY'),
                'app_secret' => env('PUSHER_APP_SECRET'),
                'cluster' => 'mt1',
                'host' => env('PUSHER_HOST'),
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'use_tls' => env('PUSHER_SCHEME', 'https') === 'https',
            ]
        );
    }
}

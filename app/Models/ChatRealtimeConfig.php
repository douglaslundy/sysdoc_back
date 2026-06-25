<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ChatRealtimeConfig extends Model
{
    public const RATE_LIMIT_DEFAULTS = [
        'rate_limit_decay_minutes' => 1,
        'rate_limit_global' => 300,
        'rate_limit_sync' => 120,
        'rate_limit_messages' => 30,
        'rate_limit_typing' => 60,
        'rate_limit_presence' => 60,
    ];

    protected $fillable = [
        'engine', 'active', 'app_id', 'app_key', 'app_secret', 'cluster',
        'host', 'port', 'scheme', 'use_tls', 'updated_by',
        'rate_limit_decay_minutes', 'rate_limit_global', 'rate_limit_sync',
        'rate_limit_messages', 'rate_limit_typing', 'rate_limit_presence',
    ];

    protected $casts = [
        'active' => 'boolean',
        'use_tls' => 'boolean',
        'port' => 'integer',
        'app_id' => 'encrypted',
        'app_key' => 'encrypted',
        'app_secret' => 'encrypted',
        'rate_limit_decay_minutes' => 'integer',
        'rate_limit_global' => 'integer',
        'rate_limit_sync' => 'integer',
        'rate_limit_messages' => 'integer',
        'rate_limit_typing' => 'integer',
        'rate_limit_presence' => 'integer',
    ];

    protected $hidden = ['app_id', 'app_key', 'app_secret'];

    public static function current(): self
    {
        if (! Schema::hasTable('chat_realtime_configs')) {
            return new static(static::fallbackAttributes());
        }

        return static::query()->first() ?? static::query()->create([
            ...static::fallbackAttributes(),
        ]);
    }

    public static function rateLimits(): array
    {
        $config = static::current();

        return [
            'rate_limit_decay_minutes' => static::normalizeLimit($config->rate_limit_decay_minutes, 1, 60, static::RATE_LIMIT_DEFAULTS['rate_limit_decay_minutes']),
            'rate_limit_global' => static::normalizeThrottle($config->rate_limit_global, static::RATE_LIMIT_DEFAULTS['rate_limit_global']),
            'rate_limit_sync' => static::normalizeThrottle($config->rate_limit_sync, static::RATE_LIMIT_DEFAULTS['rate_limit_sync']),
            'rate_limit_messages' => static::normalizeThrottle($config->rate_limit_messages, static::RATE_LIMIT_DEFAULTS['rate_limit_messages']),
            'rate_limit_typing' => static::normalizeThrottle($config->rate_limit_typing, static::RATE_LIMIT_DEFAULTS['rate_limit_typing']),
            'rate_limit_presence' => static::normalizeThrottle($config->rate_limit_presence, static::RATE_LIMIT_DEFAULTS['rate_limit_presence']),
        ];
    }

    private static function fallbackAttributes(): array
    {
        return [
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
            ...static::RATE_LIMIT_DEFAULTS,
        ];
    }

    private static function normalizeThrottle(?int $value, int $fallback): int
    {
        if ($value === null) {
            return $fallback;
        }

        return max(0, min(5000, (int) $value));
    }

    private static function normalizeLimit(?int $value, int $min, int $max, int $fallback): int
    {
        if ($value === null) {
            return $fallback;
        }

        return max($min, min($max, (int) $value));
    }
}

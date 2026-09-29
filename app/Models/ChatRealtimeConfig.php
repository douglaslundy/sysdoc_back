<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ChatRealtimeConfig extends Model
{
    private static ?bool $behaviorFlagsSupported = null;

    public const RATE_LIMIT_DEFAULTS = [
        'rate_limit_decay_minutes' => 1,
        'rate_limit_global' => 300,
        'rate_limit_sync' => 300,
        'rate_limit_messages' => 30,
        'rate_limit_typing' => 60,
        'rate_limit_presence' => 60,
    ];

    public const BEHAVIOR_DEFAULTS = [
        'auto_open_on_message' => false,
        'play_sound_on_message' => true,
    ];

    protected $fillable = [
        'engine', 'active', 'app_id', 'app_key', 'app_secret', 'cluster',
        'host', 'port', 'scheme', 'use_tls', 'updated_by',
        'rate_limit_decay_minutes', 'rate_limit_global', 'rate_limit_sync',
        'rate_limit_messages', 'rate_limit_typing', 'rate_limit_presence',
        'auto_open_on_message', 'play_sound_on_message',
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
        'auto_open_on_message' => 'boolean',
        'play_sound_on_message' => 'boolean',
    ];

    protected $hidden = ['app_id', 'app_key', 'app_secret'];

    private const MEMO_CURRENT = 'chat.realtime.config.current';
    private const MEMO_FIRST = 'chat.realtime.config.first';
    private const MEMO_TABLE = 'chat.realtime.config.table';

    protected static function booted(): void
    {
        static::saved(fn () => static::flushMemo());
        static::deleted(fn () => static::flushMemo());
    }

    /**
     * A config do chat e lida por quase toda requisicao do chat (limitadores de
     * taxa, broadcast, controllers). Memoiza no container: 1 leitura por
     * requisicao em vez de varias (hasTable + first a cada chamada). O container
     * e recriado a cada requisicao/teste, entao nunca fica desatualizado entre eles.
     */
    public static function flushMemo(): void
    {
        foreach ([self::MEMO_CURRENT, self::MEMO_FIRST, self::MEMO_TABLE] as $key) {
            app()->forgetInstance($key);
        }
    }

    public static function tableExists(): bool
    {
        if (app()->bound(self::MEMO_TABLE)) {
            return app(self::MEMO_TABLE);
        }
        $exists = Schema::hasTable('chat_realtime_configs');
        app()->instance(self::MEMO_TABLE, $exists);

        return $exists;
    }

    public static function firstOrNull(): ?self
    {
        if (app()->bound(self::MEMO_FIRST)) {
            return app(self::MEMO_FIRST)['value'];
        }
        $value = static::query()->first();
        app()->instance(self::MEMO_FIRST, ['value' => $value]);

        return $value;
    }

    public static function current(): self
    {
        if (app()->bound(self::MEMO_CURRENT)) {
            return app(self::MEMO_CURRENT);
        }

        $config = ! static::tableExists()
            ? new static(static::fallbackAttributes())
            : (static::firstOrNull() ?? static::query()->create([
                ...static::fallbackAttributes(),
            ]));

        app()->instance(self::MEMO_CURRENT, $config);

        return $config;
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

    public static function supportsBehaviorFlags(): bool
    {
        if (static::$behaviorFlagsSupported !== null) {
            return static::$behaviorFlagsSupported;
        }

        if (! Schema::hasTable('chat_realtime_configs')) {
            return static::$behaviorFlagsSupported = false;
        }

        return static::$behaviorFlagsSupported = Schema::hasColumns('chat_realtime_configs', [
            'auto_open_on_message',
            'play_sound_on_message',
        ]);
    }

    private static function fallbackAttributes(): array
    {
        return [
            'engine' => config('chat.pusher.host') ? 'soketi' : 'pusher',
            'active' => filled(config('chat.pusher.app_key')),
            'app_id' => config('chat.pusher.app_id'),
            'app_key' => config('chat.pusher.app_key'),
            'app_secret' => config('chat.pusher.app_secret'),
            'cluster' => 'mt1',
            'host' => config('chat.pusher.host'),
            'port' => (config('chat.pusher.port') ?? 443),
            'scheme' => (config('chat.pusher.scheme') ?? 'https'),
            'use_tls' => (config('chat.pusher.scheme') ?? 'https') === 'https',
            ...static::RATE_LIMIT_DEFAULTS,
            ...static::BEHAVIOR_DEFAULTS,
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

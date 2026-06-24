<?php

namespace App\Services;

use App\Models\ChatRealtimeConfig;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class ChatBroadcastConfigService
{
    public function apply(): ?ChatRealtimeConfig
    {
        $settings = $this->currentOrFallback();

        if (! $settings || ! $settings->active || ! $this->hasCredentials($settings)) {
            return $settings;
        }

        Config::set('broadcasting.default', 'pusher');
        Config::set('broadcasting.connections.pusher', [
            'driver' => 'pusher',
            'key' => $settings->app_key,
            'secret' => $settings->app_secret,
            'app_id' => $settings->app_id,
            'options' => $this->options($settings),
        ]);

        return $settings;
    }

    public function publicPayload(?ChatRealtimeConfig $settings = null): array
    {
        $settings ??= $this->currentOrFallback();

        if (! $settings || ! $settings->active || ! $settings->app_key) {
            return ['active' => false, 'engine' => null];
        }

        return [
            'active' => true,
            'engine' => $settings->engine,
            'key' => $settings->app_key,
            'cluster' => $settings->engine === 'pusher' ? ($settings->cluster ?: 'mt1') : null,
            'host' => $settings->engine === 'soketi' ? $settings->host : null,
            'port' => $settings->engine === 'soketi' ? $settings->port : null,
            'scheme' => $settings->scheme ?: 'https',
            'use_tls' => (bool) $settings->use_tls,
        ];
    }

    public function options(ChatRealtimeConfig $settings): array
    {
        if ($settings->engine === 'soketi') {
            return [
                'host' => $settings->host,
                'port' => $settings->port ?: ($settings->use_tls ? 443 : 80),
                'scheme' => $settings->scheme ?: ($settings->use_tls ? 'https' : 'http'),
                'encrypted' => (bool) $settings->use_tls,
                'useTLS' => (bool) $settings->use_tls,
            ];
        }

        return [
            'cluster' => $settings->cluster ?: 'mt1',
            'host' => 'api-'.($settings->cluster ?: 'mt1').'.pusher.com',
            'port' => 443,
            'scheme' => 'https',
            'encrypted' => true,
            'useTLS' => true,
        ];
    }

    private function currentOrFallback(): ?ChatRealtimeConfig
    {
        if (Schema::hasTable('chat_realtime_configs')) {
            try {
                $settings = ChatRealtimeConfig::query()->first();
                if ($settings) {
                    return $settings;
                }
            } catch (\Throwable) {
                // Use environment fallback during installation or key rotation.
            }
        }

        if (! env('PUSHER_APP_KEY')) {
            return null;
        }

        return new ChatRealtimeConfig([
            'engine' => env('PUSHER_HOST') ? 'soketi' : 'pusher',
            'active' => true,
            'app_id' => env('PUSHER_APP_ID'),
            'app_key' => env('PUSHER_APP_KEY'),
            'app_secret' => env('PUSHER_APP_SECRET'),
            'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
            'host' => env('PUSHER_HOST'),
            'port' => env('PUSHER_PORT', 443),
            'scheme' => env('PUSHER_SCHEME', 'https'),
            'use_tls' => env('PUSHER_SCHEME', 'https') === 'https',
        ]);
    }

    private function hasCredentials(ChatRealtimeConfig $settings): bool
    {
        return filled($settings->app_id)
            && filled($settings->app_key)
            && filled($settings->app_secret)
            && ($settings->engine !== 'soketi' || filled($settings->host));
    }
}

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
            'client_options' => $this->clientOptions(),
        ]);

        return $settings;
    }

    public function publicPayload(?ChatRealtimeConfig $settings = null): array
    {
        $settings ??= $this->currentOrFallback();
        $maxAttachmentKb = $this->effectiveMaxAttachmentKb();
        $maxAttachmentBytes = $maxAttachmentKb * 1024;
        $allowedExtensions = array_values((array) config('chat.allowed_extensions', []));

        if (! $settings || ! $settings->active || ! $settings->app_key) {
            return [
                'active' => false,
                'engine' => $settings?->engine,
                'auto_open_on_message' => ChatRealtimeConfig::supportsBehaviorFlags()
                    ? (bool) ($settings?->auto_open_on_message ?? ChatRealtimeConfig::BEHAVIOR_DEFAULTS['auto_open_on_message'])
                    : ChatRealtimeConfig::BEHAVIOR_DEFAULTS['auto_open_on_message'],
                'play_sound_on_message' => ChatRealtimeConfig::supportsBehaviorFlags()
                    ? (bool) ($settings?->play_sound_on_message ?? ChatRealtimeConfig::BEHAVIOR_DEFAULTS['play_sound_on_message'])
                    : ChatRealtimeConfig::BEHAVIOR_DEFAULTS['play_sound_on_message'],
                'max_attachment_kb' => $maxAttachmentKb,
                'max_attachment_bytes' => $maxAttachmentBytes,
                'allowed_extensions' => $allowedExtensions,
            ];
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
            'auto_open_on_message' => ChatRealtimeConfig::supportsBehaviorFlags()
                ? (bool) ($settings->auto_open_on_message ?? ChatRealtimeConfig::BEHAVIOR_DEFAULTS['auto_open_on_message'])
                : ChatRealtimeConfig::BEHAVIOR_DEFAULTS['auto_open_on_message'],
            'play_sound_on_message' => ChatRealtimeConfig::supportsBehaviorFlags()
                ? (bool) ($settings->play_sound_on_message ?? ChatRealtimeConfig::BEHAVIOR_DEFAULTS['play_sound_on_message'])
                : ChatRealtimeConfig::BEHAVIOR_DEFAULTS['play_sound_on_message'],
            'max_attachment_kb' => $maxAttachmentKb,
            'max_attachment_bytes' => $maxAttachmentBytes,
            'allowed_extensions' => $allowedExtensions,
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

    public function clientOptions(): array
    {
        $caBundle = config('chat.ca_bundle');

        return [
            'verify' => $caBundle && is_file($caBundle) ? $caBundle : true,
            'proxy' => config('chat.http_proxy', ''),
            'timeout' => 15,
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
            'auto_open_on_message' => ChatRealtimeConfig::BEHAVIOR_DEFAULTS['auto_open_on_message'],
            'play_sound_on_message' => ChatRealtimeConfig::BEHAVIOR_DEFAULTS['play_sound_on_message'],
        ]);
    }

    private function hasCredentials(ChatRealtimeConfig $settings): bool
    {
        return filled($settings->app_id)
            && filled($settings->app_key)
            && filled($settings->app_secret)
            && ($settings->engine !== 'soketi' || filled($settings->host));
    }

    private function effectiveMaxAttachmentKb(): int
    {
        $configuredKb = max(1, (int) config('chat.max_attachment_kb', 10240));
        $uploadKb = $this->iniSizeToKb(ini_get('upload_max_filesize'));
        $postKb = $this->iniSizeToKb(ini_get('post_max_size'));
        $limits = array_filter([$configuredKb, $uploadKb, $postKb], fn ($value) => (int) $value > 0);

        return (int) (empty($limits) ? $configuredKb : min($limits));
    }

    private function iniSizeToKb(string|false|null $value): int
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0;
        }

        $unit = strtolower(substr($raw, -1));
        $number = (float) $raw;

        return match ($unit) {
            'g' => (int) round($number * 1024 * 1024),
            'm' => (int) round($number * 1024),
            'k' => (int) round($number),
            default => (int) round($number / 1024),
        };
    }
}

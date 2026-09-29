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

    // Usado por ChatRealtimeService::publish() para distinguir "config aplicada" de
    // "ativo mas sem credencial" — sem isso, um broadcast() com credencial incompleta
    // falha dentro do cliente Pusher e o erro só aparece em log, nunca pro usuário.
    public function isReady(): bool
    {
        $settings = $this->currentOrFallback();

        return (bool) ($settings && $settings->active && $this->hasCredentials($settings));
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
        if (ChatRealtimeConfig::tableExists()) {
            try {
                $settings = ChatRealtimeConfig::firstOrNull();
                if ($settings) {
                    return $settings;
                }
            } catch (\Throwable) {
                // Use environment fallback during installation or key rotation.
            }
        }

        if (! config('chat.pusher.app_key')) {
            return null;
        }

        return new ChatRealtimeConfig([
            'engine' => config('chat.pusher.host') ? 'soketi' : 'pusher',
            'active' => true,
            'app_id' => config('chat.pusher.app_id'),
            'app_key' => config('chat.pusher.app_key'),
            'app_secret' => config('chat.pusher.app_secret'),
            'cluster' => (config('chat.pusher.cluster') ?? 'mt1'),
            'host' => config('chat.pusher.host'),
            'port' => (config('chat.pusher.port') ?? 443),
            'scheme' => (config('chat.pusher.scheme') ?? 'https'),
            'use_tls' => (config('chat.pusher.scheme') ?? 'https') === 'https',
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

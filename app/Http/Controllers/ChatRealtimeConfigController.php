<?php

namespace App\Http\Controllers;

use App\Models\ChatRealtimeConfig;
use App\Services\AuditService;
use App\Services\ChatBroadcastConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Pusher\Pusher;

class ChatRealtimeConfigController extends Controller
{
    public function __construct(private readonly ChatBroadcastConfigService $broadcastConfig)
    {
    }

    public function show(): JsonResponse
    {
        return response()->json($this->adminPayload(ChatRealtimeConfig::current()));
    }

    public function publicConfig(): JsonResponse
    {
        return response()->json($this->broadcastConfig->publicPayload());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(true));
        $user = $request->user();

        if (! Hash::check((string) $data['current_password'], (string) $user->password)) {
            return response()->json(['message' => 'A senha atual está incorreta.'], 422);
        }

        $config = ChatRealtimeConfig::current();
        $old = $this->auditPayload($config);
        $engine = $data['engine'];
        $values = [
            'engine' => $engine,
            'active' => (bool) $data['active'],
            'cluster' => $engine === 'pusher' ? ($data['cluster'] ?: 'mt1') : null,
            'host' => $engine === 'soketi' ? $this->normalizeHost($data['host'] ?? '') : null,
            'port' => $engine === 'soketi' ? (int) ($data['port'] ?? 6001) : null,
            'scheme' => $engine === 'soketi' && ! ($data['use_tls'] ?? false) ? 'http' : 'https',
            'use_tls' => $engine === 'soketi' ? (bool) ($data['use_tls'] ?? false) : true,
            'updated_by' => $user->id,
        ];

        foreach (['app_id', 'app_key', 'app_secret'] as $secretField) {
            if (filled($data[$secretField] ?? null)) {
                $values[$secretField] = trim((string) $data[$secretField]);
            }
        }

        $finalAppId = $values['app_id'] ?? $config->app_id;
        $finalAppKey = $values['app_key'] ?? $config->app_key;
        $finalAppSecret = $values['app_secret'] ?? $config->app_secret;
        if ($values['active'] && (! $finalAppId || ! $finalAppKey || ! $finalAppSecret)) {
            return response()->json([
                'message' => 'Informe App ID, App Key e App Secret antes de ativar o motor.',
            ], 422);
        }

        $config->update($values);
        $config->refresh();
        $this->broadcastConfig->apply();

        AuditService::record('CHAT_REALTIME_CONFIG_UPDATED', $config, $old, $this->auditPayload($config), $user);

        return response()->json($this->adminPayload($config));
    }

    public function test(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(false));
        $user = $request->user();

        if (! Hash::check((string) $data['current_password'], (string) $user->password)) {
            return response()->json(['message' => 'A senha atual está incorreta.'], 422);
        }

        $stored = ChatRealtimeConfig::current();
        $candidate = new ChatRealtimeConfig([
            'engine' => $data['engine'],
            'active' => true,
            'app_id' => filled($data['app_id'] ?? null) ? $data['app_id'] : $stored->app_id,
            'app_key' => filled($data['app_key'] ?? null) ? $data['app_key'] : $stored->app_key,
            'app_secret' => filled($data['app_secret'] ?? null) ? $data['app_secret'] : $stored->app_secret,
            'cluster' => $data['engine'] === 'pusher' ? ($data['cluster'] ?: 'mt1') : null,
            'host' => $data['engine'] === 'soketi' ? $this->normalizeHost($data['host'] ?? '') : null,
            'port' => $data['engine'] === 'soketi' ? (int) ($data['port'] ?? 6001) : null,
            'scheme' => $data['engine'] === 'soketi' && ! ($data['use_tls'] ?? false) ? 'http' : 'https',
            'use_tls' => $data['engine'] === 'soketi' ? (bool) ($data['use_tls'] ?? false) : true,
        ]);

        if (! $candidate->app_id || ! $candidate->app_key || ! $candidate->app_secret) {
            return response()->json(['message' => 'Informe App ID, App Key e App Secret.'], 422);
        }

        try {
            $client = new Pusher(
                $candidate->app_key,
                $candidate->app_secret,
                $candidate->app_id,
                $this->broadcastConfig->options($candidate)
            );
            $client->getChannels();

            AuditService::record('CHAT_REALTIME_CONFIG_TESTED', $stored, null, [
                'engine' => $candidate->engine,
                'host' => $candidate->host,
                'success' => true,
            ], $user);

            return response()->json([
                'ok' => true,
                'message' => $candidate->engine === 'soketi'
                    ? 'Conexão com o servidor Soketi validada.'
                    : 'Conexão com o Pusher Cloud validada.',
            ]);
        } catch (\Throwable) {
            AuditService::record('CHAT_REALTIME_CONFIG_TESTED', $stored, null, [
                'engine' => $candidate->engine,
                'host' => $candidate->host,
                'success' => false,
            ], $user);

            return response()->json([
                'ok' => false,
                'message' => 'Não foi possível validar o motor selecionado.',
            ], 422);
        }
    }

    private function rules(bool $saving): array
    {
        return [
            'engine' => ['required', Rule::in(['pusher', 'soketi'])],
            'active' => [$saving ? 'required' : 'nullable', 'boolean'],
            'app_id' => ['nullable', 'string', 'max:255'],
            'app_key' => ['nullable', 'string', 'max:255'],
            'app_secret' => ['nullable', 'string', 'max:500'],
            'cluster' => ['nullable', 'string', 'max:40', 'required_if:engine,pusher'],
            'host' => ['nullable', 'string', 'max:255', 'required_if:engine,soketi'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535', 'required_if:engine,soketi'],
            'scheme' => ['nullable', Rule::in(['http', 'https']), 'required_if:engine,soketi'],
            'use_tls' => ['nullable', 'boolean'],
            'current_password' => ['required', 'string'],
        ];
    }

    private function adminPayload(ChatRealtimeConfig $config): array
    {
        return [
            'engine' => $config->engine,
            'active' => (bool) $config->active,
            'cluster' => $config->cluster ?: 'mt1',
            'host' => $config->host ?: '',
            'port' => $config->port ?: 6001,
            'scheme' => $config->scheme ?: 'https',
            'use_tls' => (bool) $config->use_tls,
            'has_app_id' => filled($config->app_id),
            'has_app_key' => filled($config->app_key),
            'has_app_secret' => filled($config->app_secret),
            'updated_at' => $config->updated_at?->toISOString(),
        ];
    }

    private function auditPayload(ChatRealtimeConfig $config): array
    {
        return [
            'engine' => $config->engine,
            'active' => (bool) $config->active,
            'cluster' => $config->cluster,
            'host' => $config->host,
            'port' => $config->port,
            'scheme' => $config->scheme,
            'use_tls' => (bool) $config->use_tls,
            'has_app_id' => filled($config->app_id),
            'has_app_key' => filled($config->app_key),
            'has_app_secret' => filled($config->app_secret),
        ];
    }

    private function normalizeHost(string $host): string
    {
        $host = trim($host);
        $parsed = parse_url(str_contains($host, '://') ? $host : 'https://'.$host, PHP_URL_HOST);

        return $parsed ?: $host;
    }
}

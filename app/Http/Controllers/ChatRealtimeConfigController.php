<?php

namespace App\Http\Controllers;

use App\Models\ChatRealtimeConfig;
use App\Services\AuditService;
use App\Services\ChatBroadcastConfigService;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function setActive(Request $request): JsonResponse
    {
        $data = $request->validate(['active' => ['required', 'boolean']]);
        $config = ChatRealtimeConfig::current();

        if ($data['active'] && (! $config->app_id || ! $config->app_key || ! $config->app_secret)) {
            return response()->json([
                'message' => 'Configure App ID, App Key e App Secret antes de ativar o motor.',
            ], 422);
        }

        $old = $this->auditPayload($config);
        $config->update([
            'active' => (bool) $data['active'],
            'updated_by' => $request->user()->id,
        ]);
        $config->refresh();
        $this->broadcastConfig->apply();

        AuditService::record(
            'CHAT_REALTIME_CONFIG_STATUS_UPDATED',
            $config,
            $old,
            $this->auditPayload($config),
            $request->user()
        );

        return response()->json($this->adminPayload($config));
    }

    public function destroy(Request $request): JsonResponse
    {
        $config = ChatRealtimeConfig::current();
        $old = $this->auditPayload($config);

        $config->update([
            'active' => false,
            'app_id' => null,
            'app_key' => null,
            'app_secret' => null,
            'cluster' => 'mt1',
            'host' => null,
            'port' => null,
            'scheme' => 'https',
            'use_tls' => true,
            'updated_by' => $request->user()->id,
        ]);
        $config->refresh();

        AuditService::record(
            'CHAT_REALTIME_CONFIG_DELETED',
            $config,
            $old,
            $this->auditPayload($config),
            $request->user()
        );

        return response()->json($this->adminPayload($config));
    }

    public function test(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(false));
        $user = $request->user();

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
                $this->broadcastConfig->options($candidate),
                new Client($this->broadcastConfig->clientOptions())
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
        } catch (\Throwable $exception) {
            AuditService::record('CHAT_REALTIME_CONFIG_TESTED', $stored, null, [
                'engine' => $candidate->engine,
                'host' => $candidate->host,
                'success' => false,
            ], $user);

            $rateLimited = (int) $exception->getCode() === 429
                || str_contains(strtolower($exception->getMessage()), 'too many');
            $message = strtolower($exception->getMessage());
            $certificateError = str_contains($message, 'certificate')
                || str_contains($message, 'curl error 60');
            $connectionError = str_contains($message, 'curl error 7')
                || str_contains($message, 'couldn\'t connect')
                || str_contains($message, 'timed out');
            $authenticationError = in_array((int) $exception->getCode(), [401, 403], true)
                || str_contains($message, 'authentication')
                || str_contains($message, 'unauthorized');

            return response()->json([
                'ok' => false,
                'message' => $rateLimited
                    ? 'Too Many Attempts. Aguarde alguns instantes antes de testar novamente.'
                    : ($certificateError
                        ? 'Falha ao validar o certificado HTTPS do motor. Verifique a configuração CHAT_CA_BUNDLE no servidor.'
                        : ($connectionError
                            ? 'Não foi possível conectar ao servidor do motor. Verifique host, porta, proxy e firewall.'
                            : ($authenticationError
                                ? 'O motor recusou as credenciais. Verifique App ID, App Key, App Secret e cluster.'
                                : 'Não foi possível validar o motor selecionado.'))),
            ], $rateLimited ? 429 : 422);
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
            'configured' => filled($config->app_id) && filled($config->app_key) && filled($config->app_secret),
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

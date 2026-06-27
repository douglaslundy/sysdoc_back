<?php

namespace App\Http\Controllers;

use App\Models\NotificationChannelConfig;
use App\Services\WhatsappEvolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsappConfigController extends Controller
{
    public function __construct(private readonly WhatsappEvolutionService $whatsapp)
    {
    }

    public function show(): JsonResponse
    {
        return response()->json($this->payload($this->currentConfig()));
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'whatsapp_base_url' => 'nullable|string|max:255',
            'whatsapp_api_key' => 'nullable|string',
            'whatsapp_instance_name' => 'nullable|string|max:120',
            'whatsapp_instance_token' => 'nullable|string',
            'whatsapp_ativo' => 'boolean',
        ]);

        $config = $this->currentConfig();
        $currentSettings = $config->configuracao ?? [];
        $incomingApiKey = trim((string) $request->input('whatsapp_api_key', ''));
        $config->update([
            'ativo' => (bool) $request->boolean('whatsapp_ativo'),
            'configuracao' => [
                'whatsapp_base_url' => $request->input('whatsapp_base_url'),
                'whatsapp_api_key' => $incomingApiKey !== '' ? $incomingApiKey : ($currentSettings['whatsapp_api_key'] ?? ''),
                'whatsapp_instance_name' => $request->input('whatsapp_instance_name'),
                'whatsapp_instance_token' => $request->input('whatsapp_instance_token'),
            ],
        ]);

        return response()->json($this->payload($config->fresh()));
    }

    public function status(): JsonResponse
    {
        $config = $this->currentConfig();
        $instance = $this->resolveInstance($config);
        if (! $instance || ! $this->baseUrl($config)) {
            return response()->json(['status' => 'unknown', 'number' => null]);
        }

        $response = $this->evolutionRequest($config)
            ->get("/instance/connectionState/{$instance}");

        if (! $response->successful()) {
            $response = $this->evolutionRequest($config)
                ->get("/instance/status/{$instance}");
        }

        if (! $response->successful()) {
            return response()->json(['status' => 'unknown', 'number' => null]);
        }

        $data = $response->json() ?? [];
        $status = data_get($data, 'state')
            ?? data_get($data, 'status')
            ?? data_get($data, 'instance.state')
            ?? data_get($data, 'instance.status')
            ?? 'unknown';
        $number = data_get($data, 'number')
            ?? data_get($data, 'instance.number')
            ?? data_get($data, 'instance.waba_number')
            ?? null;

        return response()->json([
            'status' => strtolower((string) $status),
            'number' => $number,
        ]);
    }

    public function qrcode(): JsonResponse
    {
        $config = $this->currentConfig();
        $instance = $this->resolveInstance($config);
        if (! $instance || ! $this->baseUrl($config)) {
            return response()->json(['message' => 'Configuração do WhatsApp indisponível.'], 422);
        }

        $responses = [
            $this->evolutionRequest($config)->post("/instance/connect/{$instance}"),
            $this->evolutionRequest($config)->get("/instance/connect/{$instance}"),
            $this->evolutionRequest($config)->get("/instance/qrcode/{$instance}"),
        ];

        foreach ($responses as $response) {
            if (! $response->successful()) {
                continue;
            }

            $data = $response->json() ?? [];
            $qrcode = data_get($data, 'qrcode')
                ?? data_get($data, 'qrCode')
                ?? data_get($data, 'base64')
                ?? data_get($data, 'qrcode.base64')
                ?? data_get($data, 'qr')
                ?? data_get($data, 'code');

            if ($qrcode) {
                return response()->json([
                    'qrcode' => $qrcode,
                    'status' => strtolower((string) (data_get($data, 'state') ?? data_get($data, 'status') ?? 'connecting')),
                ]);
            }
        }

        return response()->json(['message' => 'Não foi possível gerar o QR code.'], 422);
    }

    public function disconnect(): JsonResponse
    {
        $config = $this->currentConfig();
        $instance = $this->resolveInstance($config);
        if (! $instance || ! $this->baseUrl($config)) {
            return response()->json(['message' => 'Configuração do WhatsApp indisponível.'], 422);
        }

        $responses = [
            $this->evolutionRequest($config)->delete("/instance/logout/{$instance}"),
            $this->evolutionRequest($config)->delete("/instance/disconnect/{$instance}"),
            $this->evolutionRequest($config)->post("/instance/logout/{$instance}"),
        ];

        foreach ($responses as $response) {
            if ($response->successful()) {
                return response()->json(['ok' => true]);
            }
        }

        return response()->json(['message' => 'Não foi possível desconectar a instância.'], 422);
    }

    public function test(Request $request): JsonResponse
    {
        $config = $this->currentConfig();
        $instance = $this->resolveInstance($config);
        if (! $instance || ! $this->baseUrl($config)) {
            return response()->json(['ok' => false, 'error' => 'Configuração indisponível.'], 422);
        }

        $response = $this->evolutionRequest($config)
            ->get("/instance/connectionState/{$instance}");

        if ($response->successful()) {
            $status = strtolower((string) (data_get($response->json(), 'state') ?? data_get($response->json(), 'status') ?? 'unknown'));

            return response()->json([
                'ok' => true,
                'status' => $status,
            ]);
        }

        return response()->json([
            'ok' => false,
            'error' => 'Não foi possível validar a conexão.',
        ], 422);
    }

    public function sendTest(Request $request): JsonResponse
    {
        $request->validate([
            'telefone' => 'required|string',
        ]);

        $config = $this->currentConfig();
        $instance = $this->resolveInstance($config);
        if (! $instance || ! $this->baseUrl($config)) {
            return response()->json(['message' => 'Configuração do WhatsApp indisponível.'], 422);
        }

        $number = preg_replace('/\D+/', '', (string) $request->input('telefone'));
        if (strlen($number) < 10) {
            return response()->json(['message' => 'Informe um número válido com DDD.'], 422);
        }

        $result = $this->whatsapp->sendTextToNumber($number, 'Mensagem de teste do Sysdoc.');

        if ($result['ok']) {
            return response()->json(['ok' => true]);
        }

        return response()->json([
            'message' => $result['error'] ?? 'Erro ao enviar a mensagem de teste.',
        ], 422);
    }

    private function currentConfig(): NotificationChannelConfig
    {
        return NotificationChannelConfig::current('whatsapp');
    }

    private function payload(NotificationChannelConfig $config): array
    {
        $settings = $config->configuracao ?? [];

        return [
            'whatsapp_base_url' => $settings['whatsapp_base_url'] ?? '',
            'whatsapp_api_key' => '',
            'whatsapp_api_key_masked' => $this->maskSecret((string) ($settings['whatsapp_api_key'] ?? '')),
            'whatsapp_instance_name' => $settings['whatsapp_instance_name'] ?? '',
            'whatsapp_instance_token' => $settings['whatsapp_instance_token'] ?? '',
            'whatsapp_ativo' => (bool) $config->ativo,
        ];
    }

    private function maskSecret(string $value): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return '';
        }

        $suffix = substr($clean, -4);

        return '********' . $suffix;
    }

    private function resolveInstance(NotificationChannelConfig $config): ?string
    {
        $instance = trim((string) data_get($config->configuracao, 'whatsapp_instance_name', ''));
        return $instance !== '' ? $instance : null;
    }

    private function baseUrl(NotificationChannelConfig $config): string
    {
        return trim((string) data_get($config->configuracao, 'whatsapp_base_url', ''));
    }

    private function apiKey(NotificationChannelConfig $config): string
    {
        return trim((string) data_get($config->configuracao, 'whatsapp_api_key', ''));
    }

    private function evolutionRequest(NotificationChannelConfig $config)
    {
        $request = Http::baseUrl(rtrim($this->baseUrl($config), '/'))
            ->acceptJson()
            ->timeout(25);

        if (! empty($this->apiKey($config))) {
            $request = $request->withHeaders([
                'apikey' => $this->apiKey($config),
            ]);
        }

        return $request;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ProtocolConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsappConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json($this->payload(ProtocolConfig::current()));
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

        $config = ProtocolConfig::current();
        $config->update($request->only([
            'whatsapp_base_url',
            'whatsapp_api_key',
            'whatsapp_instance_name',
            'whatsapp_instance_token',
            'whatsapp_ativo',
        ]));

        return response()->json($this->payload($config->fresh()));
    }

    public function status(): JsonResponse
    {
        $config = ProtocolConfig::current();
        $instance = $this->resolveInstance($config);
        if (! $instance || ! $config->whatsapp_base_url) {
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
        $config = ProtocolConfig::current();
        $instance = $this->resolveInstance($config);
        if (! $instance || ! $config->whatsapp_base_url) {
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
        $config = ProtocolConfig::current();
        $instance = $this->resolveInstance($config);
        if (! $instance || ! $config->whatsapp_base_url) {
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
        $config = ProtocolConfig::current();
        $instance = $this->resolveInstance($config);
        if (! $instance || ! $config->whatsapp_base_url) {
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

        $config = ProtocolConfig::current();
        $instance = $this->resolveInstance($config);
        if (! $instance || ! $config->whatsapp_base_url) {
            return response()->json(['message' => 'Configuração do WhatsApp indisponível.'], 422);
        }

        $number = preg_replace('/\D+/', '', (string) $request->input('telefone'));
        if (strlen($number) < 10) {
            return response()->json(['message' => 'Informe um número válido com DDD.'], 422);
        }

        $message = 'Mensagem de teste do Sysdoc.';
        $response = $this->evolutionRequest($config)->post("/message/sendText/{$instance}", [
            'number' => $number,
            'text' => $message,
            'textMessage' => ['text' => $message],
        ]);

        if ($response->successful()) {
            return response()->json(['ok' => true]);
        }

        return response()->json([
            'message' => data_get($response->json(), 'message') ?? 'Erro ao enviar a mensagem de teste.',
        ], 422);
    }

    private function payload(ProtocolConfig $config): array
    {
        return [
            'whatsapp_base_url' => $config->whatsapp_base_url ?? '',
            'whatsapp_api_key' => $config->whatsapp_api_key ?? '',
            'whatsapp_instance_name' => $config->whatsapp_instance_name ?? '',
            'whatsapp_instance_token' => $config->whatsapp_instance_token ?? '',
            'whatsapp_ativo' => (bool) $config->whatsapp_ativo,
        ];
    }

    private function resolveInstance(ProtocolConfig $config): ?string
    {
        $instance = trim((string) ($config->whatsapp_instance_name ?? ''));
        return $instance !== '' ? $instance : null;
    }

    private function evolutionRequest(ProtocolConfig $config)
    {
        $request = Http::baseUrl(rtrim((string) $config->whatsapp_base_url, '/'))
            ->acceptJson()
            ->timeout(25);

        if (! empty($config->whatsapp_api_key)) {
            $request = $request->withHeaders([
                'apikey' => $config->whatsapp_api_key,
            ]);
        }

        return $request;
    }
}

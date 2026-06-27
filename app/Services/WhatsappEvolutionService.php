<?php

namespace App\Services;

use App\Models\NotificationChannelConfig;
use App\Models\User;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;

class WhatsappEvolutionService
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function sendTextToUser(User $user, string $message): array
    {
        $number = $user->whatsappPhoneNumber();

        if (! $number) {
            return [
                'ok' => false,
                'error' => 'Usuário destinatário sem telefone cadastrado.',
            ];
        }

        return $this->sendTextToNumber($number, $message);
    }

    public function sendTextToNumber(string $number, string $message): array
    {
        $config = NotificationChannelConfig::current('whatsapp');
        $instance = $this->resolveInstance($config);
        $baseUrl = $this->baseUrl($config);

        if (! $config->ativo || ! $instance || $baseUrl === '') {
            return [
                'ok' => false,
                'error' => 'Configuração do WhatsApp indisponível.',
            ];
        }

        $normalized = $this->normalizeBrazilianNumber($number);
        if (strlen((string) $normalized) < 10) {
            return [
                'ok' => false,
                'error' => 'Número de telefone inválido para envio.',
            ];
        }

        $response = $this->request($config)->post("/message/sendText/{$instance}", [
            'number' => $normalized,
            'text' => $message,
            'textMessage' => ['text' => $message],
        ]);

        if ($response->successful()) {
            return [
                'ok' => true,
                'response' => $response->json(),
            ];
        }

        return [
            'ok' => false,
            'error' => $this->responseMessage($response),
            'response' => $response->json(),
        ];
    }

    private function request(NotificationChannelConfig $config)
    {
        $request = $this->http
            ->baseUrl(rtrim($this->baseUrl($config), '/'))
            ->acceptJson()
            ->timeout(25);

        $apiKey = $this->apiKey($config);
        if ($apiKey !== '') {
            $request = $request->withHeaders([
                'apikey' => $apiKey,
            ]);
        }

        return $request;
    }

    private function normalizeBrazilianNumber(string $number): string
    {
        $normalized = preg_replace('/\D+/', '', trim((string) $number));

        if (strlen((string) $normalized) === 10 || strlen((string) $normalized) === 11) {
            return '55' . $normalized;
        }

        return (string) $normalized;
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

    private function responseMessage(Response $response): string
    {
        return (string) (
            data_get($response->json(), 'message')
            ?: data_get($response->json(), 'response.message')
            ?: 'Erro ao enviar mensagem pelo WhatsApp.'
        );
    }
}

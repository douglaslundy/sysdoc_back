<?php

namespace App\Http\Controllers;

use App\Models\NotificationChannelConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class EmailConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json($this->payload($this->currentConfig()));
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|string|max:20',
            'from_address' => 'nullable|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'email_ativo' => 'boolean',
        ]);

        $config = $this->currentConfig();
        $config->update([
            'ativo' => (bool) $request->boolean('email_ativo'),
            'configuracao' => [
                'smtp_host' => $request->input('smtp_host'),
                'smtp_port' => $request->input('smtp_port'),
                'smtp_username' => $request->input('smtp_username'),
                'smtp_password' => $request->input('smtp_password'),
                'smtp_encryption' => $request->input('smtp_encryption'),
                'from_address' => $request->input('from_address'),
                'from_name' => $request->input('from_name'),
            ],
        ]);

        return response()->json($this->payload($config->fresh()));
    }

    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'destinatario' => 'required|email',
        ]);

        $config = $this->currentConfig();
        if (! $config->ativo) {
            return response()->json(['ok' => false, 'error' => 'Configuração de e-mail desativada.'], 422);
        }

        $this->applyMailConfig($config);

        try {
            Mail::raw('Mensagem de teste do Sysdoc.', function ($message) use ($request, $config) {
                $fromAddress = data_get($config->configuracao, 'from_address', config('mail.from.address'));
                $fromName = data_get($config->configuracao, 'from_name', config('mail.from.name'));
                $message->to($request->input('destinatario'));
                if ($fromAddress) {
                    $message->from($fromAddress, $fromName ?: null);
                }
                $message->subject('Teste de e-mail do Sysdoc');
            });

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    private function currentConfig(): NotificationChannelConfig
    {
        return NotificationChannelConfig::current('email');
    }

    private function payload(NotificationChannelConfig $config): array
    {
        $settings = $config->configuracao ?? [];

        return [
            'smtp_host' => $settings['smtp_host'] ?? '',
            'smtp_port' => $settings['smtp_port'] ?? '',
            'smtp_username' => $settings['smtp_username'] ?? '',
            'smtp_password' => $settings['smtp_password'] ?? '',
            'smtp_encryption' => $settings['smtp_encryption'] ?? '',
            'from_address' => $settings['from_address'] ?? '',
            'from_name' => $settings['from_name'] ?? '',
            'email_ativo' => (bool) $config->ativo,
        ];
    }

    private function applyMailConfig(NotificationChannelConfig $config): void
    {
        $settings = $config->configuracao ?? [];

        if (! empty($settings['smtp_host'])) {
            Config::set('mail.mailers.smtp.host', $settings['smtp_host']);
        }
        if (! empty($settings['smtp_port'])) {
            Config::set('mail.mailers.smtp.port', (int) $settings['smtp_port']);
        }
        if (! empty($settings['smtp_username'])) {
            Config::set('mail.mailers.smtp.username', $settings['smtp_username']);
        }
        if (! empty($settings['smtp_password'])) {
            Config::set('mail.mailers.smtp.password', $settings['smtp_password']);
        }
        if (! empty($settings['smtp_encryption'])) {
            Config::set('mail.mailers.smtp.encryption', $settings['smtp_encryption']);
        }
        if (! empty($settings['from_address'])) {
            Config::set('mail.from.address', $settings['from_address']);
        }
        if (! empty($settings['from_name'])) {
            Config::set('mail.from.name', $settings['from_name']);
        }
    }
}

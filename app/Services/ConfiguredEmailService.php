<?php

namespace App\Services;

use App\Models\NotificationChannelConfig;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class ConfiguredEmailService
{
    public function sendTextToUser(User $user, string $subject, string $message): array
    {
        $email = trim((string) ($user->email ?? ''));
        if ($email === '') {
            return [
                'ok' => false,
                'error' => 'Usuario destinatario sem e-mail cadastrado.',
            ];
        }

        $config = NotificationChannelConfig::current('email');
        if (! $config->ativo) {
            return [
                'ok' => false,
                'error' => 'Configuracao de e-mail indisponivel.',
            ];
        }

        $this->applyMailConfig($config);

        try {
            Mail::raw($message, function ($mail) use ($email, $subject, $config) {
                $fromAddress = data_get($config->configuracao, 'from_address', config('mail.from.address'));
                $fromName = data_get($config->configuracao, 'from_name', config('mail.from.name'));
                $mail->to($email);
                if ($fromAddress) {
                    $mail->from($fromAddress, $fromName ?: null);
                }
                $mail->subject($subject);
            });

            return ['ok' => true];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
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

<?php

namespace App\Services\Secretarias;

use App\Models\User;
use App\Services\ConfiguredEmailService;
use App\Services\WhatsappEvolutionService;

class GestorAlert
{
    /** Avisa todos os admins ativos que um gestor atribuiu o perfil gestor. Nunca lança. */
    public static function perfilGestorAtribuido(User $ator, User $alvo): void
    {
        $assunto = 'Perfil Gestor atribuído';
        $mensagem = "O gestor {$ator->name} atribuiu o perfil Gestor a {$alvo->name} ({$alvo->email}).";
        $meta = ['origem' => 'perfil_gestor_atribuido'];

        try {
            $admins = User::where('profile', 'admin')->where('active', true)->get();
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        foreach ($admins as $admin) {
            try {
                app(WhatsappEvolutionService::class)->sendTextToUser($admin, $mensagem, $meta);
            } catch (\Throwable $e) {
                report($e);
            }
            try {
                app(ConfiguredEmailService::class)->sendTextToUser($admin, $assunto, $mensagem, $meta);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\ProtocolAlert;
use Illuminate\Database\Seeder;

/**
 * Alerta ao perfil admin sempre que um protocolo é cadastrado. Idempotente: não duplica nem
 * sobrescreve ajustes feitos depois na tela de Alertas.
 *
 *   php artisan db:seed --class=ProtocolAdminAlertSeeder
 */
class ProtocolAdminAlertSeeder extends Seeder
{
    public function run(): void
    {
        ProtocolAlert::firstOrCreate(
            ['modulo' => 'protocolo', 'gatilho' => 'protocolo_criado', 'nome' => 'Novo protocolo cadastrado (administradores)'],
            [
                'descricao' => 'Avisa o perfil admin quando qualquer protocolo é cadastrado.',
                'canais' => ['whatsapp'],
                'destinatarios' => ['administrador'],
                'template' => 'Novo protocolo {{protocolo_numero}} ({{protocolo_assunto}}) cadastrado por {{protocolo_autor}}.',
                'ativo' => true,
                'prevenir_duplicidade' => true,
            ]
        );
    }
}

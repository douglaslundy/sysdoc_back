<?php

namespace Tests\Feature;

use App\Models\ProtocolAlert;
use App\Models\User;
use App\Services\SystemAlertService;
use App\Services\WhatsappEvolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertDeferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_alertas_do_sistema_so_sao_enviados_depois_da_resposta(): void
    {
        config(['chat.defer_broadcast' => true]);
        User::factory()->create(['profile' => 'admin', 'active' => true, 'phone' => '62999990000']);
        ProtocolAlert::create([
            'nome' => 'Teste', 'modulo' => 'chat', 'gatilho' => 'chat_mensagem_enviada',
            'canais' => ['whatsapp'], 'destinatarios' => ['administrador'], 'template' => 'Ola', 'ativo' => true,
        ]);

        $counter = new \stdClass();
        $counter->sent = 0;
        $fake = new class($counter) extends WhatsappEvolutionService {
            public function __construct(private \stdClass $counter)
            {
            }

            public function sendTextToUser(User $user, string $message): array
            {
                $this->counter->sent++;

                return ['ok' => true];
            }
        };
        $this->app->instance(WhatsappEvolutionService::class, $fake);

        app(SystemAlertService::class)->dispatch('chat', 'chat_mensagem_enviada', []);

        $this->assertSame(0, $counter->sent, 'Nao pode enviar durante a requisicao.');
        $this->app->terminate();
        $this->assertSame(1, $counter->sent, 'Deve enviar ao terminar a requisicao.');
    }

    public function test_em_console_o_alerta_continua_sendo_enviado_na_hora(): void
    {
        config(['chat.defer_broadcast' => null]);
        User::factory()->create(['profile' => 'admin', 'active' => true, 'phone' => '62999990000']);
        ProtocolAlert::create([
            'nome' => 'Teste', 'modulo' => 'chat', 'gatilho' => 'chat_mensagem_enviada',
            'canais' => ['whatsapp'], 'destinatarios' => ['administrador'], 'template' => 'Ola', 'ativo' => true,
        ]);

        $counter = new \stdClass();
        $counter->sent = 0;
        $fake = new class($counter) extends WhatsappEvolutionService {
            public function __construct(private \stdClass $counter)
            {
            }

            public function sendTextToUser(User $user, string $message): array
            {
                $this->counter->sent++;

                return ['ok' => true];
            }
        };
        $this->app->instance(WhatsappEvolutionService::class, $fake);

        app(SystemAlertService::class)->dispatch('chat', 'chat_mensagem_enviada', []);

        $this->assertSame(1, $counter->sent);
    }
}

<?php

namespace Tests\Feature;

use App\Models\NotificationChannelConfig;
use App\Models\ProtocolConfig;
use App\Models\ProtocolNotification;
use App\Models\ProtocolOrganizationalUnit;
use App\Models\ProtocolType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProtocolWhatsappAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerta_whatsapp_de_protocolo_usa_telefone_cadastrado_do_usuario(): void
    {
        Http::fake([
            'http://evolution.test/message/sendText/*' => Http::response(['message' => 'ok'], 200),
        ]);

        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
            'cpf' => '12345678909',
        ]);
        $responsavel = User::factory()->create([
            'active' => true,
            'phone' => '62999991111',
        ]);
        $origem = ProtocolOrganizationalUnit::create([
            'tipo' => 'secretaria',
            'nome' => 'Secretaria de Saúde',
            'ativo' => true,
        ]);
        $tipo = ProtocolType::create([
            'codigo' => 'OFICIO',
            'nome' => 'Ofício',
            'ativo' => true,
        ]);

        ProtocolConfig::current()->update([
            'notify_whatsapp' => true,
        ]);

        NotificationChannelConfig::current('whatsapp')->update([
            'ativo' => true,
            'configuracao' => [
                'whatsapp_base_url' => 'http://evolution.test',
                'whatsapp_api_key' => 'key-test',
                'whatsapp_instance_name' => 'sysdoc',
            ],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/protocolos', [
                'assunto' => 'Teste de envio',
                'descricao' => 'Criando protocolo com alerta WhatsApp.',
                'tipo' => $tipo->codigo,
                'origem_unit_id' => $origem->id,
                'destino_user_id' => $responsavel->id,
            ]);

        $response->assertCreated();

        $protocolId = $response->json('id');

        $this->assertDatabaseHas('protocol_notifications', [
            'protocol_id' => $protocolId,
            'user_id' => $responsavel->id,
            'canal' => 'whatsapp',
            'status_envio' => 'enviado',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://evolution.test/message/sendText/sysdoc'
                && data_get($request->data(), 'number') === '62999991111';
        });

        $notification = ProtocolNotification::query()
            ->where('protocol_id', $protocolId)
            ->where('canal', 'whatsapp')
            ->first();

        $this->assertNotNull($notification?->enviada_em);
        $this->assertNull($notification?->erro);
    }
}

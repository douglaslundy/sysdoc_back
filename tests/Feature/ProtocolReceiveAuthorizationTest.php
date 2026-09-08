<?php

namespace Tests\Feature;

use App\Models\Protocol;
use App\Models\ProtocolOrganizationalUnit;
use App\Models\ProtocolUserUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtocolReceiveAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_remetente_nao_pode_receber_o_proprio_protocolo_enviado_a_outro_usuario(): void
    {
        $remetente = User::factory()->create(['profile' => 'user', 'active' => true]);
        $destinatario = User::factory()->create(['profile' => 'user', 'active' => true]);

        $protocol = Protocol::create([
            'numero' => 'PRT-TESTE-100',
            'assunto' => 'Protocolo enviado a outro usuario',
            'tipo' => 'administrativo',
            'status' => 'novo',
            'prioridade' => 'normal',
            'solicitante_tipo' => 'interno',
            'responsavel_atual_id' => $destinatario->id,
            'criado_por_id' => $remetente->id,
            'novo' => true,
        ]);

        $this->actingAs($remetente, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/receber")
            ->assertStatus(403);

        $this->assertDatabaseHas('protocols', [
            'id' => $protocol->id,
            'status' => 'novo',
            'recebido_em' => null,
        ]);
    }

    public function test_destinatario_real_consegue_receber_o_protocolo(): void
    {
        $remetente = User::factory()->create(['profile' => 'user', 'active' => true]);
        $destinatario = User::factory()->create(['profile' => 'user', 'active' => true]);

        $protocol = Protocol::create([
            'numero' => 'PRT-TESTE-101',
            'assunto' => 'Protocolo enviado a outro usuario',
            'tipo' => 'administrativo',
            'status' => 'novo',
            'prioridade' => 'normal',
            'solicitante_tipo' => 'interno',
            'responsavel_atual_id' => $destinatario->id,
            'criado_por_id' => $remetente->id,
            'novo' => true,
        ]);

        $this->actingAs($destinatario, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/receber")
            ->assertOk();

        $this->assertDatabaseHas('protocols', [
            'id' => $protocol->id,
            'status' => 'recebido',
        ]);
    }

    public function test_membro_da_unidade_de_destino_pode_receber_quando_nao_ha_usuario_especifico_atribuido(): void
    {
        $remetente = User::factory()->create(['profile' => 'user', 'active' => true]);
        $membroDestino = User::factory()->create(['profile' => 'user', 'active' => true]);

        $unidadeDestino = ProtocolOrganizationalUnit::create([
            'tipo' => 'secretaria',
            'codigo' => 'SEC-TESTE',
            'nome' => 'Secretaria de Teste',
            'ativo' => true,
        ]);

        ProtocolUserUnit::create([
            'user_id' => $membroDestino->id,
            'protocol_organizational_unit_id' => $unidadeDestino->id,
            'papel' => 'membro',
            'ativo' => true,
        ]);

        $protocol = Protocol::create([
            'numero' => 'PRT-TESTE-102',
            'assunto' => 'Protocolo enviado a uma secretaria, sem usuario especifico',
            'tipo' => 'administrativo',
            'status' => 'novo',
            'prioridade' => 'normal',
            'solicitante_tipo' => 'interno',
            'destino_unit_id' => $unidadeDestino->id,
            'responsavel_atual_id' => null,
            'criado_por_id' => $remetente->id,
            'novo' => true,
        ]);

        $this->actingAs($membroDestino, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/receber")
            ->assertOk();

        $this->assertDatabaseHas('protocols', [
            'id' => $protocol->id,
            'status' => 'recebido',
        ]);
    }
}

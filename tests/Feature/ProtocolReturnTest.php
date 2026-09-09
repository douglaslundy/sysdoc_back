<?php

namespace Tests\Feature;

use App\Models\Protocol;
use App\Models\ProtocolMovement;
use App\Models\ProtocolOrganizationalUnit;
use App\Models\ProtocolUserUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtocolReturnTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $profile = 'user'): User
    {
        return User::factory()->create(['profile' => $profile, 'active' => true]);
    }

    /**
     * Cadeia A cria -> B recebe -> B encaminha para C.
     * Deixa C como responsavel atual, com um movimento 'encaminhado' feito por B.
     */
    private function protocolForwardedFromBtoC(User $a, User $b, User $c): Protocol
    {
        $protocol = Protocol::create([
            'numero' => 'PRT-RET-'.uniqid(),
            'assunto' => 'Protocolo para teste de devolucao',
            'tipo' => 'administrativo',
            'status' => 'novo',
            'prioridade' => 'normal',
            'solicitante_tipo' => 'interno',
            'responsavel_atual_id' => $b->id,
            'criado_por_id' => $a->id,
            'novo' => true,
        ]);

        $this->actingAs($b, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/receber")
            ->assertOk();

        $this->actingAs($b, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/encaminhar", [
                'destino_user_id' => $c->id,
            ])
            ->assertOk();

        return $protocol->fresh();
    }

    public function test_responsavel_atual_devolve_para_quem_encaminhou_por_ultimo(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $c = $this->makeUser();

        $protocol = $this->protocolForwardedFromBtoC($a, $b, $c);
        $this->assertSame($c->id, $protocol->responsavel_atual_id);

        $this->actingAs($c, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/devolver", [
                'motivo' => 'Falta anexar o parecer juridico. Favor incluir e reenviar.',
            ])
            ->assertOk();

        $protocol->refresh();
        $this->assertSame('devolvido', $protocol->status);
        $this->assertSame($b->id, $protocol->responsavel_atual_id);
        $this->assertNotNull($protocol->devolvido_em);
        $this->assertStringContainsString('parecer juridico', $protocol->justificativa_devolucao);

        $this->assertDatabaseHas('protocol_movements', [
            'protocol_id' => $protocol->id,
            'acao' => 'devolvido',
            'status_anterior' => 'encaminhado',
            'status_novo' => 'devolvido',
            'user_id' => $c->id,
        ]);

        $movement = ProtocolMovement::where('protocol_id', $protocol->id)
            ->where('acao', 'devolvido')
            ->first();
        $this->assertSame('Falta anexar o parecer juridico. Favor incluir e reenviar.', $movement->dados['motivo'] ?? null);
    }

    public function test_motivo_e_obrigatorio(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $c = $this->makeUser();
        $protocol = $this->protocolForwardedFromBtoC($a, $b, $c);

        $this->actingAs($c, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/devolver", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');

        $this->assertSame('encaminhado', $protocol->fresh()->status);
    }

    public function test_motivo_muito_curto_e_rejeitado(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $c = $this->makeUser();
        $protocol = $this->protocolForwardedFromBtoC($a, $b, $c);

        $this->actingAs($c, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/devolver", ['motivo' => 'oi'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');
    }

    public function test_protocolo_nunca_encaminhado_volta_para_o_criador(): void
    {
        $criador = $this->makeUser();
        $responsavel = $this->makeUser();

        $protocol = Protocol::create([
            'numero' => 'PRT-RET-'.uniqid(),
            'assunto' => 'Protocolo endereçado direto a um responsavel',
            'tipo' => 'administrativo',
            'status' => 'novo',
            'prioridade' => 'normal',
            'solicitante_tipo' => 'interno',
            'responsavel_atual_id' => $responsavel->id,
            'criado_por_id' => $criador->id,
            'novo' => true,
        ]);

        $this->actingAs($responsavel, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/devolver", [
                'motivo' => 'Assunto não é da minha competência, devolvendo ao criador.',
            ])
            ->assertOk();

        $protocol->refresh();
        $this->assertSame('devolvido', $protocol->status);
        $this->assertSame($criador->id, $protocol->responsavel_atual_id);
    }

    public function test_quem_nao_e_o_responsavel_atual_nao_pode_devolver(): void
    {
        $criador = $this->makeUser();
        $b = $this->makeUser();
        $c = $this->makeUser();
        // Encaminhado de B para C: C é o responsável, o criador ainda enxerga o protocolo.
        $protocol = $this->protocolForwardedFromBtoC($criador, $b, $c);

        $this->actingAs($criador, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/devolver", [
                'motivo' => 'Tentando devolver sem ser o responsável atual.',
            ])
            ->assertStatus(403);

        $this->assertSame('encaminhado', $protocol->fresh()->status);
    }

    public function test_responsavel_que_e_o_proprio_remetente_nao_pode_devolver(): void
    {
        $criador = $this->makeUser();

        $protocol = Protocol::create([
            'numero' => 'PRT-RET-'.uniqid(),
            'assunto' => 'Protocolo criado e mantido com o próprio criador',
            'tipo' => 'administrativo',
            'status' => 'novo',
            'prioridade' => 'normal',
            'solicitante_tipo' => 'interno',
            'responsavel_atual_id' => $criador->id,
            'criado_por_id' => $criador->id,
            'novo' => true,
        ]);

        $this->actingAs($criador, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/devolver", [
                'motivo' => 'Não faz sentido devolver para mim mesmo.',
            ])
            ->assertStatus(422);
    }

    public function test_protocolo_encerrado_nao_pode_ser_devolvido(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $c = $this->makeUser();
        $protocol = $this->protocolForwardedFromBtoC($a, $b, $c);
        Protocol::where('id', $protocol->id)->update(['status' => 'encerrado']);

        $this->actingAs($c, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/devolver", [
                'motivo' => 'Tentando devolver um protocolo já encerrado.',
            ])
            ->assertStatus(422);
    }

    public function test_apos_devolver_o_remetente_consegue_reencaminhar(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $c = $this->makeUser();
        $protocol = $this->protocolForwardedFromBtoC($a, $b, $c);

        $this->actingAs($c, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/devolver", [
                'motivo' => 'Corrigir os dados do solicitante antes de seguir.',
            ])
            ->assertOk();

        // B (remetente) agora é o responsável e reencaminha para C novamente.
        $this->actingAs($b, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/encaminhar", [
                'destino_user_id' => $c->id,
                'observacao' => 'Dados corrigidos.',
            ])
            ->assertOk();

        $protocol->refresh();
        $this->assertSame('encaminhado', $protocol->status);
        $this->assertSame($c->id, $protocol->responsavel_atual_id);
    }

    public function test_devolucao_notifica_o_remetente(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $c = $this->makeUser();
        $protocol = $this->protocolForwardedFromBtoC($a, $b, $c);

        $this->actingAs($c, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/devolver", [
                'motivo' => 'Reenviar com a documentação completa.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('protocol_notifications', [
            'protocol_id' => $protocol->id,
            'user_id' => $b->id,
            'canal' => 'interna',
        ]);
    }

    public function test_detalhe_informa_quando_o_responsavel_pode_devolver_e_para_quem(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $c = $this->makeUser();
        $protocol = $this->protocolForwardedFromBtoC($a, $b, $c);

        $this->actingAs($c, 'sanctum')
            ->getJson("/api/protocolos/{$protocol->id}")
            ->assertOk()
            ->assertJsonPath('pode_devolver', true)
            ->assertJsonPath('devolver_para', $b->name);
    }

    public function test_detalhe_nao_oferece_devolucao_para_quem_nao_tem_remetente(): void
    {
        $criador = $this->makeUser();

        $protocol = Protocol::create([
            'numero' => 'PRT-RET-'.uniqid(),
            'assunto' => 'Protocolo mantido com o criador',
            'tipo' => 'administrativo',
            'status' => 'novo',
            'prioridade' => 'normal',
            'solicitante_tipo' => 'interno',
            'responsavel_atual_id' => $criador->id,
            'criado_por_id' => $criador->id,
            'novo' => true,
        ]);

        $this->actingAs($criador, 'sanctum')
            ->getJson("/api/protocolos/{$protocol->id}")
            ->assertOk()
            ->assertJsonPath('pode_devolver', false);
    }

    public function test_devolucao_ignora_encaminhamentos_feitos_pelo_proprio_responsavel(): void
    {
        // A cria -> B; B encaminha -> C; C encaminha -> D; D devolve -> C;
        // agora C quer devolver e deve voltar para B (último a encaminhar que não é C).
        $a = $this->makeUser();
        $b = $this->makeUser();
        $c = $this->makeUser();
        $d = $this->makeUser();

        $protocol = $this->protocolForwardedFromBtoC($a, $b, $c);

        $this->actingAs($c, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/encaminhar", ['destino_user_id' => $d->id])
            ->assertOk();

        $this->actingAs($d, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/devolver", ['motivo' => 'Precisa voltar uma etapa.'])
            ->assertOk();
        $this->assertSame($c->id, $protocol->fresh()->responsavel_atual_id);

        $this->actingAs($c, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/devolver", ['motivo' => 'O problema é da etapa anterior, com o B.'])
            ->assertOk();

        $this->assertSame($b->id, $protocol->fresh()->responsavel_atual_id);
    }

    public function test_remetente_inativo_faz_a_devolucao_ser_endereçada_a_unidade_dele(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $c = $this->makeUser();

        $unidadeB = ProtocolOrganizationalUnit::create([
            'tipo' => 'secretaria',
            'codigo' => 'SEC-B',
            'nome' => 'Secretaria do B',
            'ativo' => true,
        ]);
        ProtocolUserUnit::create([
            'user_id' => $b->id,
            'protocol_organizational_unit_id' => $unidadeB->id,
            'papel' => 'membro',
            'ativo' => true,
        ]);

        $protocol = $this->protocolForwardedFromBtoC($a, $b, $c);

        $b->update(['active' => false]);

        $this->actingAs($c, 'sanctum')
            ->postJson("/api/protocolos/{$protocol->id}/devolver", ['motivo' => 'Devolvendo, mas o remetente foi desativado.'])
            ->assertOk();

        $protocol->refresh();
        $this->assertSame('devolvido', $protocol->status);
        $this->assertNull($protocol->responsavel_atual_id);
        $this->assertSame($unidadeB->id, $protocol->destino_unit_id);
    }
}

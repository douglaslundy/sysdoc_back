<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlmoxarifadoRequisicaoApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_aprovacao_sem_saldo_retorna_mensagem_e_mantem_requisicao_recebida(): void
    {
        $user = User::factory()->create(['active' => true, 'profile' => 'admin']);
        $secretariaId = DB::table('almoxarifado_secretarias')->insertGetId([
            'nome' => 'Secretaria de Teste',
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $produtoId = DB::table('almoxarifado_produtos')->insertGetId([
            'nome' => 'Papel A4',
            'codigo_interno' => 'PAPEL-A4-TESTE',
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $requisicaoId = DB::table('almoxarifado_requisicoes')->insertGetId([
            'numero' => 'REQ-TESTE-001',
            'almoxarifado_secretaria_id' => $secretariaId,
            'solicitante' => 'Usuário de Teste',
            'data_solicitacao' => now()->toDateString(),
            'status' => 'recebida',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('almoxarifado_requisicao_itens')->insert([
            'almoxarifado_requisicao_id' => $requisicaoId,
            'almoxarifado_produto_id' => $produtoId,
            'quantidade_solicitada' => 1,
            'quantidade_atendida' => 0,
            'quantidade_entregue' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/almoxarifado/requisicoes/{$requisicaoId}/status", [
                'status' => 'aprovada',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Saldo insuficiente para Papel A4. Disponível: 0,000; solicitado: 1,000.');

        $this->assertDatabaseHas('almoxarifado_requisicoes', [
            'id' => $requisicaoId,
            'status' => 'recebida',
        ]);
    }

    public function test_fluxo_aprovar_separar_e_entregar_atualiza_saldos(): void
    {
        [$user, $requisicaoId, $produtoId, $secretariaId] = $this->createRequisicaoComSaldo(10);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/almoxarifado/requisicoes/{$requisicaoId}/status", ['status' => 'aprovada'])
            ->assertOk()
            ->assertJsonPath('status', 'aprovada');

        $this->assertEstoque($produtoId, $secretariaId, 90, 10, 0, 0);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/almoxarifado/requisicoes/{$requisicaoId}/status", ['status' => 'em_separacao'])
            ->assertOk()
            ->assertJsonPath('status', 'em_separacao');

        $this->assertEstoque($produtoId, $secretariaId, 90, 0, 10, 0);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/almoxarifado/requisicoes/{$requisicaoId}/status", ['status' => 'entregue'])
            ->assertOk()
            ->assertJsonPath('status', 'entregue');

        $this->assertEstoque($produtoId, $secretariaId, 90, 0, 0, 10);
        $this->assertDatabaseHas('almoxarifado_requisicao_itens', [
            'almoxarifado_requisicao_id' => $requisicaoId,
            'quantidade_atendida' => 10,
        ]);
    }

    public function test_cancelamento_apos_aprovacao_estorna_reserva(): void
    {
        [$user, $requisicaoId, $produtoId, $secretariaId] = $this->createRequisicaoComSaldo(8);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/almoxarifado/requisicoes/{$requisicaoId}/status", ['status' => 'aprovada'])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/almoxarifado/requisicoes/{$requisicaoId}/status", ['status' => 'cancelada'])
            ->assertOk()
            ->assertJsonPath('status', 'cancelada');

        $this->assertEstoque($produtoId, $secretariaId, 100, 0, 0, 0);
    }

    private function createRequisicaoComSaldo(float $quantidade): array
    {
        $user = User::factory()->create(['active' => true, 'profile' => 'admin']);
        $suffix = str_replace('.', '', uniqid('', true));
        $secretariaId = DB::table('almoxarifado_secretarias')->insertGetId([
            'nome' => "Secretaria {$suffix}",
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $produtoId = DB::table('almoxarifado_produtos')->insertGetId([
            'nome' => "Produto {$suffix}",
            'codigo_interno' => "PROD-{$suffix}",
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('almoxarifado_estoques')->insert([
            'almoxarifado_produto_id' => $produtoId,
            'almoxarifado_secretaria_id' => $secretariaId,
            'quantidade_disponivel' => 100,
            'quantidade_reservada' => 0,
            'quantidade_em_separacao' => 0,
            'quantidade_entregue' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $requisicaoId = DB::table('almoxarifado_requisicoes')->insertGetId([
            'numero' => "REQ-{$suffix}",
            'almoxarifado_secretaria_id' => $secretariaId,
            'solicitante' => 'Usuário de Teste',
            'data_solicitacao' => now()->toDateString(),
            'status' => 'recebida',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('almoxarifado_requisicao_itens')->insert([
            'almoxarifado_requisicao_id' => $requisicaoId,
            'almoxarifado_produto_id' => $produtoId,
            'quantidade_solicitada' => $quantidade,
            'quantidade_atendida' => 0,
            'quantidade_entregue' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $requisicaoId, $produtoId, $secretariaId];
    }

    public function test_criacao_ignora_solicitante_e_data_do_cliente(): void
    {
        $user = User::factory()->create(['active' => true, 'profile' => 'admin', 'name' => 'Requisitante Real']);
        $secretariaId = DB::table('almoxarifado_secretarias')->insertGetId([
            'nome' => 'Secretaria Automática',
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $produtoId = DB::table('almoxarifado_produtos')->insertGetId([
            'nome' => 'Produto Automático',
            'codigo_interno' => 'AUTO-001',
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/almoxarifado/requisicoes', [
            'almoxarifado_secretaria_id' => $secretariaId,
            'solicitante' => 'Nome forjado',
            'data_solicitacao' => '2000-01-01',
            'itens' => [[
                'almoxarifado_produto_id' => $produtoId,
                'quantidade_solicitada' => 1,
            ]],
        ])->assertCreated();

        $response->assertJsonPath('solicitante', 'Requisitante Real')
            ->assertJsonPath('requisitante_user_id', $user->id);
        $this->assertDatabaseHas('almoxarifado_requisicoes', [
            'id' => $response->json('id'),
            'requisitante_user_id' => $user->id,
            'data_solicitacao' => now()->toDateString(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->get("/api/almoxarifado/requisicoes/{$response->json('id')}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_usuario_sem_permissao_nao_aprova_requisicao(): void
    {
        $user = User::factory()->create(['active' => true, 'profile' => 'user']);
        $requisicaoId = DB::table('almoxarifado_requisicoes')->insertGetId([
            'numero' => 'REQ-SEM-PERMISSAO',
            'almoxarifado_secretaria_id' => DB::table('almoxarifado_secretarias')->insertGetId([
                'nome' => 'Secretaria Restrita',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'solicitante' => $user->name,
            'requisitante_user_id' => $user->id,
            'data_solicitacao' => now()->toDateString(),
            'status' => 'recebida',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/almoxarifado/requisicoes/{$requisicaoId}/status", ['status' => 'aprovada'])
            ->assertForbidden();
    }

    private function assertEstoque(
        int $produtoId,
        int $secretariaId,
        float $disponivel,
        float $reservado,
        float $separacao,
        float $entregue
    ): void {
        $this->assertDatabaseHas('almoxarifado_estoques', [
            'almoxarifado_produto_id' => $produtoId,
            'almoxarifado_secretaria_id' => $secretariaId,
            'quantidade_disponivel' => $disponivel,
            'quantidade_reservada' => $reservado,
            'quantidade_em_separacao' => $separacao,
            'quantidade_entregue' => $entregue,
        ]);
    }
}

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
        $user = User::factory()->create(['active' => true]);
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
}

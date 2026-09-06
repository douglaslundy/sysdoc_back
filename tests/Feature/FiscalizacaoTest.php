<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\Fiscalizacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscalizacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $userSemPermissao;

    private Estabelecimento $estabelecimento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->userSemPermissao = User::factory()->create(['profile' => 'user', 'active' => true]);
        $this->estabelecimento = Estabelecimento::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'estabelecimento_id' => $this->estabelecimento->id,
            'data_visita' => '2026-09-06',
            'resultado' => 'Conforme',
            'observacoes' => 'Visita de rotina.',
        ], $overrides);
    }

    public function test_admin_cria_fiscalizacao_e_fiscal_id_vem_do_usuario_autenticado(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/fiscalizacoes', $this->payload(['fiscal_id' => 999999]));

        $response->assertStatus(201);
        $response->assertJsonPath('fiscal_id', $this->admin->id);
        $response->assertJsonPath('estabelecimento.nome_estabelecimento', $this->estabelecimento->nome_estabelecimento);
    }

    public function test_lista_filtra_por_estabelecimento(): void
    {
        Fiscalizacao::create($this->payload(['fiscal_id' => $this->admin->id, 'resultado' => 'Conforme']));
        $outro = Estabelecimento::factory()->create();
        Fiscalizacao::create([
            'estabelecimento_id' => $outro->id,
            'fiscal_id' => $this->admin->id,
            'data_visita' => '2026-09-06',
            'resultado' => 'Não conforme',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/fiscalizacoes?estabelecimento_id='.$this->estabelecimento->id);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Conforme', $response->json('data.0.resultado'));
    }

    public function test_lista_filtra_por_resultado(): void
    {
        // Create two fiscalizações for the SAME estabelecimento with different resultados
        Fiscalizacao::create($this->payload(['fiscal_id' => $this->admin->id, 'resultado' => 'Conforme']));
        Fiscalizacao::create($this->payload(['fiscal_id' => $this->admin->id, 'resultado' => 'Não conforme']));

        // Filter by resultado = 'Conforme'
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/fiscalizacoes?resultado=Conforme');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Conforme', $response->json('data.0.resultado'));
    }

    public function test_lista_filtra_por_busca_nome_estabelecimento(): void
    {
        $estabelecimento1 = Estabelecimento::factory()->create(['nome_estabelecimento' => 'Padaria Central']);
        $estabelecimento2 = Estabelecimento::factory()->create(['nome_estabelecimento' => 'Restaurante da Cidade']);

        Fiscalizacao::create([
            'estabelecimento_id' => $estabelecimento1->id,
            'fiscal_id' => $this->admin->id,
            'data_visita' => '2026-09-06',
            'resultado' => 'Conforme',
        ]);
        Fiscalizacao::create([
            'estabelecimento_id' => $estabelecimento2->id,
            'fiscal_id' => $this->admin->id,
            'data_visita' => '2026-09-06',
            'resultado' => 'Conforme',
        ]);

        // Search for 'Padaria'
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/fiscalizacoes?busca=Padaria');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Padaria Central', $response->json('data.0.estabelecimento.nome_estabelecimento'));
    }

    public function test_criar_sem_permissao_de_pagina_retorna_403(): void
    {
        $response = $this->actingAs($this->userSemPermissao, 'sanctum')
            ->postJson('/api/fiscalizacoes', $this->payload());

        $response->assertStatus(403);
    }

    public function test_editar_atualiza_resultado(): void
    {
        $fiscalizacao = Fiscalizacao::create($this->payload(['fiscal_id' => $this->admin->id]));

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/fiscalizacoes/{$fiscalizacao->id}", $this->payload(['resultado' => 'Auto de infração']));

        $response->assertOk();
        $response->assertJsonPath('resultado', 'Auto de infração');
    }

    public function test_excluir_remove_registro(): void
    {
        $fiscalizacao = Fiscalizacao::create($this->payload(['fiscal_id' => $this->admin->id]));

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/fiscalizacoes/{$fiscalizacao->id}")
            ->assertOk();

        $this->assertSoftDeleted('fiscalizacoes', ['id' => $fiscalizacao->id]);
    }

    public function test_resultado_invalido_falha_validacao(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/fiscalizacoes', $this->payload(['resultado' => 'Inventado']));

        $response->assertStatus(422);
    }
}

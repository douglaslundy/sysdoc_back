<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstabelecimentoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    public function test_listagem_paginada_retorna_estrutura_correta(): void
    {
        Estabelecimento::factory()->count(20)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/estabelecimentos');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'nome_responsavel', 'nome_estabelecimento', 'endereco', 'cnaes']],
                'meta' => ['current_page', 'last_page', 'total', 'per_page'],
            ]);
    }

    public function test_busca_por_nome_estabelecimento(): void
    {
        Estabelecimento::factory()->create(['nome_estabelecimento' => 'Farmácia Saúde']);
        Estabelecimento::factory()->create(['nome_estabelecimento' => 'Mercado Central']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/estabelecimentos?busca=Farmácia');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Farmácia Saúde', $response->json('data.0.nome_estabelecimento'));
    }

    public function test_criacao_de_estabelecimento(): void
    {
        $payload = [
            'nome_responsavel'     => 'João Silva',
            'nome_estabelecimento' => 'Clínica São João',
            'endereco'             => 'Rua das Flores, 100',
            'cnaes'                => '86.30-3-04',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/estabelecimentos', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('nome_estabelecimento', 'Clínica São João');

        $this->assertDatabaseHas('estabelecimentos', ['nome_estabelecimento' => 'Clínica São João']);
    }

    public function test_criacao_falha_sem_campos_obrigatorios(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/estabelecimentos', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nome_responsavel', 'nome_estabelecimento', 'endereco', 'cnaes']);
    }

    public function test_atualizacao_de_estabelecimento(): void
    {
        $estabelecimento = Estabelecimento::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/estabelecimentos/{$estabelecimento->id}", [
                'nome_estabelecimento' => 'Nome Atualizado',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('nome_estabelecimento', 'Nome Atualizado');
    }

    public function test_soft_delete_de_estabelecimento(): void
    {
        $estabelecimento = Estabelecimento::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/estabelecimentos/{$estabelecimento->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('estabelecimentos', ['id' => $estabelecimento->id]);
    }

    public function test_endpoint_select_retorna_campos_resumidos(): void
    {
        Estabelecimento::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/estabelecimentos/select');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(3, $data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('nome_estabelecimento', $data[0]);
        $this->assertArrayNotHasKey('cnaes', $data[0]);
    }
}

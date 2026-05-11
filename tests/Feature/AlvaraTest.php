<?php

namespace Tests\Feature;

use App\Models\Alvara;
use App\Models\Estabelecimento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlvaraTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Estabelecimento $estabelecimento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user            = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->estabelecimento = Estabelecimento::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'estabelecimento_id' => $this->estabelecimento->id,
            'nivel_risco'        => '1',
            'data_alvara'        => '2026-05-11',
            'vencimento_alvara'  => '2027-05-11',
            'contato'            => '(11) 99999-9999',
        ], $overrides);
    }

    public function test_criacao_gera_numero_automaticamente(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload());

        $response->assertStatus(201);
        $this->assertNotNull($response->json('numero_alvara'));
        $this->assertMatchesRegularExpression('/^\d{2}-\d{2}\/\d{4}$/', $response->json('numero_alvara'));
    }

    public function test_numero_alvara_enviado_pelo_frontend_e_ignorado(): void
    {
        $payload              = $this->payload();
        $payload['numero_alvara'] = '99-99/9999';

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $payload);

        $response->assertStatus(201);
        $this->assertNotSame('99-99/9999', $response->json('numero_alvara'));
    }

    public function test_segundo_alvara_no_mesmo_mes_tem_sequencial_incrementado(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload(['data_alvara' => '2026-05-01']));

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload(['data_alvara' => '2026-05-15']));

        $this->assertSame('02-05/2026', $response->json('numero_alvara'));
    }

    public function test_primeiro_alvara_no_novo_mes_reinicia_sequencial(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload(['data_alvara' => '2026-05-01']));

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload(['data_alvara' => '2026-06-01']));

        $this->assertSame('01-06/2026', $response->json('numero_alvara'));
    }

    public function test_nivel_risco_invalido_retorna_422(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload(['nivel_risco' => 'INVALIDO']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nivel_risco']);
    }

    public function test_vencimento_anterior_a_data_retorna_422(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload([
                'data_alvara'       => '2026-05-11',
                'vencimento_alvara' => '2026-01-01',
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vencimento_alvara']);
    }

    public function test_vencimento_nulo_e_permitido(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload(['vencimento_alvara' => null]));

        $response->assertStatus(201);
        $this->assertNull($response->json('vencimento_alvara'));
    }

    public function test_nivel_risco_na_e_aceito(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload(['nivel_risco' => 'N/A']));

        $response->assertStatus(201)
            ->assertJsonPath('nivel_risco', 'N/A');
    }

    public function test_atualizacao_nao_altera_numero_alvara(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload());

        $alvara = Alvara::first();
        $numeroOriginal = $alvara->numero_alvara;

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/alvaras/{$alvara->id}", [
                'nivel_risco' => '3',
                'numero_alvara' => '99-99/9999',
            ]);

        $response->assertStatus(200);
        $this->assertSame($numeroOriginal, $response->json('numero_alvara'));
    }

    public function test_soft_delete_de_alvara(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload());

        $alvara = Alvara::first();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/alvaras/{$alvara->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('alvaras', ['id' => $alvara->id]);
    }

    public function test_listagem_paginada(): void
    {
        $this->actingAs($this->user, 'sanctum');

        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/alvaras', $this->payload(['data_alvara' => "2026-0{$i}-01"]));
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/alvaras?per_page=3');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'numero_alvara', 'nivel_risco', 'estabelecimento']],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_filtro_por_nivel_risco(): void
    {
        $this->actingAs($this->user, 'sanctum');
        $this->postJson('/api/alvaras', $this->payload(['nivel_risco' => '1', 'data_alvara' => '2026-01-01']));
        $this->postJson('/api/alvaras', $this->payload(['nivel_risco' => '3', 'data_alvara' => '2026-02-01']));

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/alvaras?nivel_risco=1');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('1', $response->json('data.0.nivel_risco'));
    }

    public function test_estabelecimento_inexistente_retorna_422(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload(['estabelecimento_id' => 99999]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['estabelecimento_id']);
    }

    public function test_criacao_com_status_valido(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload(['status' => 'Vigente']));

        $response->assertStatus(201)
            ->assertJsonPath('status', 'Vigente');
    }

    public function test_status_padrao_e_nao_requerido(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload());

        $response->assertStatus(201)
            ->assertJsonPath('status', 'Não requerido');
    }

    public function test_status_invalido_retorna_422(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/alvaras', $this->payload(['status' => 'StatusInvalido']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_filtro_por_status(): void
    {
        $this->actingAs($this->user, 'sanctum');
        $this->postJson('/api/alvaras', $this->payload(['status' => 'Vigente', 'data_alvara' => '2026-01-01']));
        $this->postJson('/api/alvaras', $this->payload(['status' => 'Vencido', 'data_alvara' => '2026-02-01']));

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/alvaras?status=Vigente');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Vigente', $response->json('data.0.status'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\PeticaoMotivo;
use App\Models\ProtocolOrganizationalUnit;
use App\Models\SystemPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsPages;
use Tests\TestCase;

class PeticaoMotivoTest extends TestCase
{
    use GrantsPages;
    use RefreshDatabase;

    private User $admin;

    private ProtocolOrganizationalUnit $unidade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->unidade = ProtocolOrganizationalUnit::create([
            'tipo' => 'departamento', 'codigo' => 'VISA', 'nome' => 'Vigilância Sanitária', 'ativo' => true,
        ]);
    }

    private function as(User $user, string $method, string $uri, array $data = [])
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user, 'sanctum')->json($method, $uri, $data);
    }

    public function test_admin_cadastra_edita_e_exclui_motivo(): void
    {
        $id = $this->as($this->admin, 'POST', '/api/peticao-motivos', [
            'nome' => 'Denúncia', 'descricao' => 'Irregularidade sanitária', 'unit_id' => $this->unidade->id, 'ordem' => 1,
        ])->assertCreated()->assertJsonPath('nome', 'Denúncia')->assertJsonPath('ativo', true)->json('id');

        $this->as($this->admin, 'PUT', "/api/peticao-motivos/{$id}", [
            'nome' => 'Denúncia sanitária', 'unit_id' => $this->unidade->id, 'ativo' => false,
        ])->assertOk()->assertJsonPath('ativo', false);

        $this->as($this->admin, 'GET', '/api/peticao-motivos')->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.nome', 'Denúncia sanitária')
            ->assertJsonPath('0.unit.nome', 'Vigilância Sanitária');

        $this->as($this->admin, 'DELETE', "/api/peticao-motivos/{$id}")->assertOk();
        $this->assertDatabaseMissing('peticao_motivos', ['id' => $id]);
    }

    public function test_nome_e_obrigatorio_e_unico_e_unidade_precisa_existir(): void
    {
        PeticaoMotivo::create(['nome' => 'Vistoria', 'unit_id' => $this->unidade->id]);

        $this->as($this->admin, 'POST', '/api/peticao-motivos', ['nome' => ''])->assertStatus(422);
        $this->as($this->admin, 'POST', '/api/peticao-motivos', ['nome' => 'Vistoria'])->assertStatus(422);
        $this->as($this->admin, 'POST', '/api/peticao-motivos', ['nome' => 'Outro', 'unit_id' => 99999])->assertStatus(422);
    }

    public function test_usuario_sem_a_pagina_nao_acessa_e_com_a_pagina_acessa(): void
    {
        $user = User::factory()->create(['profile' => 'user', 'active' => true]);

        $this->as($user, 'GET', '/api/peticao-motivos')->assertForbidden();
        $this->as($user, 'POST', '/api/peticao-motivos', ['nome' => 'X'])->assertForbidden();

        $this->grantPages('user', ['/peticao-motivos']);
        $this->as($user, 'GET', '/api/peticao-motivos')->assertOk();
        $this->as($user, 'POST', '/api/peticao-motivos', ['nome' => 'Liberado'])->assertCreated();
    }

    public function test_lista_publica_traz_so_ativos_em_ordem_sem_expor_a_unidade(): void
    {
        PeticaoMotivo::create(['nome' => 'Vistoria', 'descricao' => 'Solicitar vistoria', 'unit_id' => $this->unidade->id, 'ordem' => 2]);
        PeticaoMotivo::create(['nome' => 'Denúncia', 'unit_id' => $this->unidade->id, 'ordem' => 1]);
        PeticaoMotivo::create(['nome' => 'Antigo', 'ativo' => false, 'ordem' => 0]);

        $resposta = $this->getJson('/api/public/petition/reasons')->assertOk();

        $resposta->assertJsonCount(2)
            ->assertJsonPath('0.nome', 'Denúncia')
            ->assertJsonPath('1.nome', 'Vistoria')
            ->assertJsonPath('1.descricao', 'Solicitar vistoria');
        $this->assertArrayNotHasKey('unit_id', $resposta->json('0'));
    }

    public function test_pagina_de_cadastro_existe_para_ser_liberada_em_perfis(): void
    {
        $this->assertTrue(SystemPage::where('path', '/peticao-motivos')->exists());
    }
}

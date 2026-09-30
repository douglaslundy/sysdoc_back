<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\Fiscalizacao;
use App\Models\User;
use App\Services\Fiscalizacao\FiscalizacaoTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FiscalizacaoHistoricoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Fiscalizacao $fiscalizacao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true, 'name' => 'Fiscal Admin']);
        $this->fiscalizacao = Fiscalizacao::create([
            'estabelecimento_id' => Estabelecimento::factory()->create()->id,
            'data_visita' => '2026-09-06', 'resultado' => 'Conforme',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function historico(User $user, ?int $id = null)
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user, 'sanctum')->getJson('/api/fiscalizacoes/'.($id ?? $this->fiscalizacao->id).'/historico');
    }

    public function test_lista_tudo_do_mais_recente_para_o_mais_antigo(): void
    {
        $timeline = app(FiscalizacaoTimeline::class);
        Carbon::setTestNow('2026-09-30 08:00:00');
        $timeline->registrar($this->fiscalizacao, 'criada', 'Fiscalização criada', false, $this->admin->id);
        Carbon::setTestNow('2026-09-30 09:00:00');
        $timeline->registrar($this->fiscalizacao, 'mensagem_publica', 'Vistoria marcada.', true, $this->admin->id);

        $response = $this->historico($this->admin)->assertOk();

        $response->assertJsonCount(2)
            ->assertJsonPath('0.titulo', 'Mensagem ao denunciante')
            ->assertJsonPath('0.detalhe', 'Vistoria marcada.')
            ->assertJsonPath('0.usuario', 'Fiscal Admin')
            ->assertJsonPath('0.publico', true)
            ->assertJsonPath('1.titulo', 'Fiscalização criada')
            ->assertJsonPath('1.publico', false);
        $this->assertNotEmpty($response->json('0.data'));
    }

    public function test_denuncia_recebida_aparece_como_denunciante(): void
    {
        app(FiscalizacaoTimeline::class)->registrar($this->fiscalizacao, 'denuncia_recebida', 'Denúncia recebida', true, null);

        $this->historico($this->admin)->assertOk()
            ->assertJsonPath('0.titulo', 'Denúncia recebida')
            ->assertJsonPath('0.usuario', 'Denunciante');
    }

    public function test_adiciona_movimentacao_manual_interna_ou_publica(): void
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/fiscalizacoes/{$this->fiscalizacao->id}/movimentacoes", ['descricao' => 'Ligar para o responsável.', 'publico' => false])
            ->assertCreated();
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/fiscalizacoes/{$this->fiscalizacao->id}/movimentacoes", ['descricao' => 'Visita agendada.', 'publico' => true])
            ->assertCreated();

        $mov = $this->fiscalizacao->movimentacoes()->get();
        $this->assertSame(['observacao', 'observacao'], $mov->pluck('acao')->all());
        $this->assertSame([false, true], $mov->pluck('publico')->all());
        $this->assertSame($this->admin->id, $mov[0]->user_id);
    }

    public function test_descricao_e_obrigatoria(): void
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/fiscalizacoes/{$this->fiscalizacao->id}/movimentacoes", ['descricao' => ''])
            ->assertStatus(422);
    }

    public function test_exige_a_pagina_fiscalizacoes(): void
    {
        $user = User::factory()->create(['profile' => 'user', 'active' => true]);

        $this->historico($user)->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/fiscalizacoes/{$this->fiscalizacao->id}/movimentacoes", ['descricao' => 'x'])
            ->assertForbidden();
    }

    public function test_fiscalizacao_inexistente_devolve_404(): void
    {
        $this->historico($this->admin, 999999)->assertNotFound();
    }
}

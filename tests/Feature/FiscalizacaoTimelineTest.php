<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\Fiscalizacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscalizacaoTimelineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Estabelecimento $estabelecimento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->estabelecimento = Estabelecimento::factory()->create();
    }

    private function criar(array $extra = []): Fiscalizacao
    {
        $this->app['auth']->forgetGuards();
        $id = $this->actingAs($this->admin, 'sanctum')->postJson('/api/fiscalizacoes', $extra + [
            'estabelecimento_id' => $this->estabelecimento->id, 'data_visita' => '2026-09-06', 'resultado' => 'Pendente de apuração',
        ])->assertCreated()->json('id');

        return Fiscalizacao::findOrFail($id);
    }

    private function atualizar(Fiscalizacao $f, array $dados): void
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')->putJson("/api/fiscalizacoes/{$f->id}", $dados)->assertOk();
    }

    public function test_criacao_gera_protocolo_e_movimentacao_interna(): void
    {
        $f = $this->criar();

        $this->assertMatchesRegularExpression('/^FIS-\d{4}-\d{6}$/', $f->protocolo);
        $mov = $f->movimentacoes()->get();
        $this->assertCount(1, $mov);
        $this->assertSame('criada', $mov[0]->acao);
        $this->assertFalse($mov[0]->publico);
        $this->assertSame($this->admin->id, $mov[0]->user_id);
    }

    public function test_mudar_a_situacao_registra_de_para_e_nao_registra_sem_mudanca(): void
    {
        $f = $this->criar();

        $this->atualizar($f, ['resultado' => 'Conforme']);
        $mov = $f->movimentacoes()->where('acao', 'situacao_alterada')->get();
        $this->assertCount(1, $mov);
        $this->assertSame('Situação: Pendente de apuração → Conforme', $mov[0]->descricao);
        $this->assertSame(['de' => 'Pendente de apuração', 'para' => 'Conforme'], $mov[0]->dados);
        $this->assertFalse($mov[0]->publico);

        $this->atualizar($f, ['resultado' => 'Conforme', 'observacoes' => 'só uma nota']);
        $this->assertSame(1, $f->movimentacoes()->where('acao', 'situacao_alterada')->count());
    }

    public function test_mensagem_ao_denunciante_gera_movimentacao_publica(): void
    {
        $f = $this->criar();

        $this->atualizar($f, [
            'resultado' => 'Notificação', 'visivel_ao_denunciante' => true, 'mensagem_publica' => 'Estabelecimento notificado.',
        ]);

        $publicas = $f->movimentacoes()->where('publico', true)->get();
        $this->assertCount(1, $publicas);
        $this->assertSame('mensagem_publica', $publicas[0]->acao);
        $this->assertSame('Estabelecimento notificado.', $publicas[0]->descricao);
    }

    public function test_sem_a_flag_nada_fica_publico(): void
    {
        $f = $this->criar();

        $this->atualizar($f, ['resultado' => 'Auto de infração', 'mensagem_publica' => 'texto sem flag']);

        $this->assertSame(0, $f->movimentacoes()->where('publico', true)->count());
    }

    public function test_excluir_nao_quebra(): void
    {
        $f = $this->criar();

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/fiscalizacoes/{$f->id}")->assertOk();
    }
}

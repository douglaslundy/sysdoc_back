<?php

namespace Tests\Feature;

use App\Models\SincronizacaoCidadao;
use App\Models\SincronizacaoItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConformidadeCidadaoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->user  = User::factory()->create(['profile' => 'user',  'active' => true]);
    }

    public function test_analisar_requer_autenticacao(): void
    {
        $r = $this->postJson('/api/conformidade-cidadao/analisar');
        $this->assertEquals(401, $r->status());
    }

    public function test_historico_requer_autenticacao(): void
    {
        $r = $this->getJson('/api/conformidade-cidadao/historico');
        $this->assertEquals(401, $r->status());
    }

    public function test_rotas_existem(): void
    {
        $r = $this->actingAs($this->admin, 'sanctum')->getJson('/api/conformidade-cidadao/historico');
        $this->assertNotEquals(404, $r->status());
    }

    public function test_historico_retorna_lista_vazia_inicialmente(): void
    {
        $r = $this->actingAs($this->admin, 'sanctum')->getJson('/api/conformidade-cidadao/historico');
        $r->assertStatus(200)->assertJsonStructure(['data', 'meta' => ['total', 'per_page', 'current_page']]);
        $this->assertCount(0, $r->json('data'));
    }

    public function test_status_retorna_404_para_job_inexistente(): void
    {
        $r = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/conformidade-cidadao/status/uuid-inexistente');
        $r->assertStatus(404);
    }

    public function test_aplicar_retorna_409_se_status_nao_e_preview_ready(): void
    {
        $sync = SincronizacaoCidadao::create([
            'job_id'       => Str::uuid(),
            'status'       => 'analyzing',
            'iniciado_por' => $this->admin->id,
        ]);

        $r = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/conformidade-cidadao/aplicar/{$sync->job_id}");
        $r->assertStatus(409);
    }

    public function test_analisar_retorna_409_se_ja_existe_sync_em_andamento(): void
    {
        SincronizacaoCidadao::create([
            'job_id'       => Str::uuid(),
            'status'       => 'analyzing',
            'iniciado_por' => $this->admin->id,
        ]);

        $this->mock(\App\Services\ConformidadeCidadaoService::class, function ($mock) {
            $mock->shouldNotReceive('analisar');
        });

        $r = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/conformidade-cidadao/analisar');
        $r->assertStatus(409);
    }

    public function test_status_retorna_estrutura_correta(): void
    {
        $sync = SincronizacaoCidadao::create([
            'job_id'       => Str::uuid(),
            'status'       => 'preview_ready',
            'total_esus'   => 100,
            'total_sysdoc' => 90,
            'preview_criados'    => 10,
            'preview_atualizados' => 5,
            'preview_obitos'     => 2,
            'iniciado_por' => $this->admin->id,
        ]);

        $r = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/conformidade-cidadao/status/{$sync->job_id}");

        $r->assertStatus(200)->assertJsonStructure([
            'job_id', 'status', 'total_esus', 'total_sysdoc',
            'preview_criados', 'preview_atualizados', 'preview_obitos',
        ]);
        $this->assertEquals('preview_ready', $r->json('status'));
    }

    public function test_erros_retorna_404_para_job_inexistente(): void
    {
        $r = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/conformidade-cidadao/erros/uuid-inexistente');
        $r->assertStatus(404);
    }

    public function test_erros_retorna_itens_com_erro(): void
    {
        $sync = SincronizacaoCidadao::create([
            'job_id'       => Str::uuid(),
            'status'       => 'completed',
            'iniciado_por' => $this->admin->id,
        ]);

        SincronizacaoItem::create([
            'sincronizacao_id' => $sync->id,
            'acao'             => 'atualizar',
            'cpf'              => '12345678900',
            'cns'              => null,
            'nome_esus'        => 'Fulano',
            'client_id'        => 1,
            'payload'          => ['foo' => 'bar'],
            'aplicado'         => false,
            'erro'             => 'Falha ao atualizar cliente',
        ]);

        $r = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/conformidade-cidadao/erros/{$sync->job_id}");

        $r->assertStatus(200)->assertJsonStructure([
            'data' => [['id', 'acao', 'cpf', 'nome_esus', 'erro']],
            'meta' => ['total', 'per_page', 'current_page', 'last_page'],
        ]);
        $this->assertEquals(1, $r->json('meta.total'));
    }
}

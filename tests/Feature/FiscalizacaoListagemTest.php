<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\Fiscalizacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscalizacaoListagemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Fiscalizacao $interna;

    private Fiscalizacao $denuncia;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->interna = Fiscalizacao::create([
            'estabelecimento_id' => Estabelecimento::factory()->create(['nome_estabelecimento' => 'Padaria Central'])->id,
            'data_visita' => '2026-09-06', 'resultado' => 'Conforme', 'origem' => 'interna', 'protocolo' => 'FIS-2026-000001',
        ]);
        $this->denuncia = Fiscalizacao::create([
            'resultado' => 'Pendente de apuração', 'origem' => 'peticao', 'protocolo' => 'FIS-2026-000002',
            'estabelecimento_nome_informado' => 'Bar do Zé', 'assunto' => 'Higiene',
        ]);
    }

    private function listar(string $query = ''): array
    {
        $this->app['auth']->forgetGuards();

        return collect($this->actingAs($this->admin, 'sanctum')->getJson("/api/fiscalizacoes{$query}")->assertOk()->json('data'))
            ->pluck('id')->all();
    }

    public function test_denuncia_sem_estabelecimento_aparece_na_listagem(): void
    {
        $this->assertEqualsCanonicalizing([$this->interna->id, $this->denuncia->id], $this->listar());
    }

    public function test_filtra_por_origem(): void
    {
        $this->assertSame([$this->denuncia->id], $this->listar('?origem=denuncia'));
        $this->assertSame([$this->interna->id], $this->listar('?origem=interna'));
    }

    public function test_busca_por_protocolo_estabelecimento_e_nome_informado(): void
    {
        $this->assertSame([$this->denuncia->id], $this->listar('?busca=FIS-2026-000002'));
        $this->assertSame([$this->interna->id], $this->listar('?busca=Padaria'));
        $this->assertSame([$this->denuncia->id], $this->listar('?busca='.urlencode('Bar do')));
    }
}

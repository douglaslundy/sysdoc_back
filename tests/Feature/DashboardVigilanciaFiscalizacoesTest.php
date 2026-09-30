<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\Fiscalizacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardVigilanciaFiscalizacoesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow('2026-09-15 10:00:00');
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function visita(string $data, string $resultado): Fiscalizacao
    {
        return Fiscalizacao::create([
            'estabelecimento_id' => Estabelecimento::factory()->create()->id,
            'data_visita' => $data, 'resultado' => $resultado, 'origem' => 'interna',
        ]);
    }

    private function denuncia(string $resultado, string $recebidaEm): Fiscalizacao
    {
        $f = Fiscalizacao::create(['resultado' => $resultado, 'origem' => 'denuncia']);
        $f->forceFill(['created_at' => $recebidaEm])->saveQuietly();

        return $f;
    }

    private function kpis(): array
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($this->admin, 'sanctum')->getJson('/api/dashboard/vigilancia')->assertOk()->json('fiscalizacoes');
    }

    public function test_indicadores_do_ano_do_mes_denuncias_pendentes_e_autos(): void
    {
        $this->visita('2026-09-03', 'Conforme');            // ano + mês
        $this->visita('2026-05-10', 'Auto de infração');    // ano
        $this->visita('2026-02-01', 'Não conforme');        // ano
        $this->visita('2025-12-20', 'Auto de infração');    // ano passado: fora
        $this->denuncia('Pendente de apuração', '2026-09-10 08:00:00'); // ano + mês + pendente
        $this->denuncia('Pendente de apuração', '2026-03-01 08:00:00'); // ano + pendente
        $this->denuncia('Conforme', '2026-09-11 08:00:00');            // ano + mês, já apurada

        $this->assertSame([
            'no_ano' => 6,
            'no_mes' => 3,
            'denuncias_pendentes' => 2,
            'autos_infracao_ano' => 1,
        ], $this->kpis());
    }

    public function test_sem_dados_devolve_zeros(): void
    {
        $this->assertSame(
            ['no_ano' => 0, 'no_mes' => 0, 'denuncias_pendentes' => 0, 'autos_infracao_ano' => 0],
            $this->kpis()
        );
    }

    public function test_fiscalizacao_excluida_nao_conta(): void
    {
        $f = $this->visita('2026-09-03', 'Auto de infração');
        $f->delete();

        $this->assertSame(0, $this->kpis()['no_ano']);
        $this->assertSame(0, $this->kpis()['autos_infracao_ano']);
    }

    public function test_totais_de_alvara_continuam_no_mesmo_payload(): void
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')->getJson('/api/dashboard/vigilancia')
            ->assertOk()
            ->assertJsonStructure(['totais' => ['estabelecimentos', 'alvaras'], 'por_status', 'fiscalizacoes' => ['no_ano']]);
    }
}

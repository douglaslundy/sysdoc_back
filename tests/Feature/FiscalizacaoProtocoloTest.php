<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\Fiscalizacao;
use App\Models\User;
use App\Services\Fiscalizacao\FiscalizacaoProtocolo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FiscalizacaoProtocoloTest extends TestCase
{
    use RefreshDatabase;

    public function test_monta_o_protocolo_com_ano_e_id_de_seis_digitos(): void
    {
        $this->assertSame('FIS-2026-000123', FiscalizacaoProtocolo::for(123, Carbon::parse('2026-05-01')));
        $this->assertSame('FIS-2027-000001', FiscalizacaoProtocolo::for(1, Carbon::parse('2027-01-01')));
    }

    public function test_criar_pela_api_devolve_protocolo_e_origem_interna(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $estabelecimento = Estabelecimento::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/fiscalizacoes', [
            'estabelecimento_id' => $estabelecimento->id, 'data_visita' => '2026-09-06', 'resultado' => 'Conforme',
        ])->assertCreated();

        $this->assertMatchesRegularExpression('/^FIS-\d{4}-\d{6}$/', $response->json('protocolo'));
        $this->assertSame('interna', $response->json('origem'));
        $this->assertSame($response->json('protocolo'), Fiscalizacao::find($response->json('id'))->protocolo);
    }

    public function test_banco_aceita_fiscalizacao_sem_estabelecimento_e_sem_data_de_visita(): void
    {
        $id = DB::table('fiscalizacoes')->insertGetId([
            'resultado' => 'Pendente de apuração', 'origem' => 'denuncia', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertNull(DB::table('fiscalizacoes')->where('id', $id)->value('estabelecimento_id'));
        $this->assertNull(DB::table('fiscalizacoes')->where('id', $id)->value('data_visita'));
    }

    public function test_migration_preenche_o_protocolo_das_fiscalizacoes_antigas_sem_duplicar(): void
    {
        $estabelecimento = Estabelecimento::factory()->create();
        $id = DB::table('fiscalizacoes')->insertGetId([
            'estabelecimento_id' => $estabelecimento->id, 'data_visita' => '2025-03-10', 'resultado' => 'Conforme',
            'protocolo' => null, 'created_at' => '2025-03-10 10:00:00', 'updated_at' => '2025-03-10 10:00:00',
        ]);

        $migration = require base_path('database/migrations/2026_09_30_100000_add_protocolo_e_denuncia_to_fiscalizacoes.php');
        $migration->up();
        $migration->up();

        $this->assertSame(sprintf('FIS-2025-%06d', $id), DB::table('fiscalizacoes')->where('id', $id)->value('protocolo'));
    }

    public function test_hash_da_senha_de_consulta_nunca_sai_no_json(): void
    {
        $fiscalizacao = Fiscalizacao::create(['resultado' => 'Pendente de apuração', 'origem' => 'denuncia']);
        $fiscalizacao->forceFill(['senha_consulta_hash' => 'hash-secreto'])->save();

        $this->assertArrayNotHasKey('senha_consulta_hash', $fiscalizacao->fresh()->toArray());
        $this->assertStringNotContainsString('hash-secreto', $fiscalizacao->fresh()->toJson());
    }

    public function test_resource_mostra_o_nome_informado_quando_nao_ha_estabelecimento_cadastrado(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $fiscalizacao = Fiscalizacao::create([
            'resultado' => 'Pendente de apuração', 'origem' => 'denuncia',
            'estabelecimento_nome_informado' => 'Bar do Zé', 'assunto' => 'Higiene',
        ]);

        $this->actingAs($admin, 'sanctum')->getJson("/api/fiscalizacoes/{$fiscalizacao->id}")
            ->assertOk()
            ->assertJsonPath('estabelecimento.nome_estabelecimento', 'Bar do Zé')
            ->assertJsonPath('origem', 'denuncia')
            ->assertJsonPath('assunto', 'Higiene');
    }
}

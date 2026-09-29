<?php

namespace Tests\Feature;

use App\Models\Protocol;
use App\Models\ProtocolAttachment;
use App\Models\ProtocolComment;
use App\Models\ProtocolOrganizationalUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProtocolVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeProtocol(User $creator, ?User $responsavel): Protocol
    {
        return Protocol::create([
            'numero' => 'PRT-VIS-'.random_int(1000, 9999),
            'assunto' => 'Protocolo de visibilidade',
            'tipo' => 'administrativo',
            'status' => 'novo',
            'prioridade' => 'normal',
            'solicitante_tipo' => 'interno',
            'responsavel_atual_id' => $responsavel?->id,
            'criado_por_id' => $creator->id,
            'novo' => true,
        ]);
    }

    public function test_remetente_ve_o_protocolo_enviado_na_propria_caixa(): void
    {
        $remetente = User::factory()->create(['profile' => 'user', 'active' => true]);
        $destinatario = User::factory()->create(['profile' => 'user', 'active' => true]);
        $protocol = $this->makeProtocol($remetente, $destinatario);

        $ids = collect(
            $this->actingAs($remetente, 'sanctum')->getJson('/api/protocolos/caixa-entrada')->assertOk()->json('data')
        )->pluck('id');

        $this->assertTrue($ids->contains($protocol->id));
    }

    public function test_receber_duas_vezes_nao_gera_nova_movimentacao(): void
    {
        $remetente = User::factory()->create(['profile' => 'user', 'active' => true]);
        $destinatario = User::factory()->create(['profile' => 'user', 'active' => true]);
        $protocol = $this->makeProtocol($remetente, $destinatario);

        Carbon::setTestNow('2026-01-01 10:00:00');
        $this->actingAs($destinatario, 'sanctum')->postJson("/api/protocolos/{$protocol->id}/receber")->assertOk();
        $primeiro = $protocol->fresh()->recebido_em;

        Carbon::setTestNow('2026-01-02 10:00:00');
        $this->actingAs($destinatario, 'sanctum')->postJson("/api/protocolos/{$protocol->id}/receber")->assertOk();

        $this->assertEquals($primeiro, $protocol->fresh()->recebido_em);
        $this->assertSame(1, \App\Models\AuditLog::where('action', 'RECEBIDO')->count());
        Carbon::setTestNow();
    }

    public function test_encaminhar_reabre_o_recebimento_para_o_novo_responsavel(): void
    {
        $a = User::factory()->create(['profile' => 'user', 'active' => true]);
        $b = User::factory()->create(['profile' => 'user', 'active' => true]);
        $unit = ProtocolOrganizationalUnit::create(['tipo' => 'secretaria', 'nome' => 'Saude', 'ativo' => true]);
        $protocol = $this->makeProtocol($a, $a);
        $protocol->update(['recebido_em' => now()]);

        $this->actingAs($a, 'sanctum')->postJson("/api/protocolos/{$protocol->id}/encaminhar", [
            'destino_unit_id' => $unit->id,
            'destino_user_id' => $b->id,
        ])->assertOk();

        $this->assertNull($protocol->fresh()->recebido_em);
    }

    public function test_quem_encaminhou_so_ve_o_que_ocorreu_ate_o_encaminhamento(): void
    {
        $a = User::factory()->create(['profile' => 'user', 'active' => true]);
        $b = User::factory()->create(['profile' => 'user', 'active' => true]);
        $unit = ProtocolOrganizationalUnit::create(['tipo' => 'secretaria', 'nome' => 'Saude', 'ativo' => true]);
        $protocol = $this->makeProtocol($a, $a);

        Carbon::setTestNow('2026-01-01 10:00:00');
        $this->actingAs($a, 'sanctum')->postJson("/api/protocolos/{$protocol->id}/comentarios", ['conteudo' => 'antes'])->assertOk();
        $antes = ProtocolAttachment::create([
            'protocol_id' => $protocol->id, 'user_id' => $a->id, 'nome_original' => 'antes.pdf',
            'caminho' => 'x/antes.pdf', 'mime_type' => 'application/pdf', 'tamanho_bytes' => 1, 'ativo' => true,
        ]);

        Carbon::setTestNow('2026-01-01 11:00:00');
        $this->actingAs($a, 'sanctum')->postJson("/api/protocolos/{$protocol->id}/encaminhar", [
            'destino_unit_id' => $unit->id,
            'destino_user_id' => $b->id,
        ])->assertOk();

        Carbon::setTestNow('2026-01-01 12:00:00');
        $this->actingAs($b, 'sanctum')->postJson("/api/protocolos/{$protocol->id}/comentarios", ['conteudo' => 'depois'])->assertOk();
        $depois = ProtocolAttachment::create([
            'protocol_id' => $protocol->id, 'user_id' => $b->id, 'nome_original' => 'depois.pdf',
            'caminho' => 'x/depois.pdf', 'mime_type' => 'application/pdf', 'tamanho_bytes' => 1, 'ativo' => true,
        ]);

        $json = $this->actingAs($a, 'sanctum')->getJson("/api/protocolos/{$protocol->id}")->assertOk()->json();
        $comentarios = collect($json['comments'])->pluck('conteudo')->all();
        $this->assertSame(['antes'], $comentarios);
        $this->assertSame(['antes.pdf'], collect($json['attachments'])->pluck('nome_original')->all());
        $this->assertNotContains('comentado', collect($json['movements'])->where('created_at', '>', '2026-01-01T11:00:01')->pluck('acao')->all());

        $historico = $this->actingAs($a, 'sanctum')->getJson("/api/protocolos/{$protocol->id}/historico")->assertOk()->json();
        $this->assertTrue(collect($historico)->every(fn ($m) => Carbon::parse($m['created_at'])->lte(Carbon::parse('2026-01-01 11:00:00'))));

        $this->actingAs($a, 'sanctum')->get("/api/protocolos/anexos/{$depois->id}/download")->assertStatus(404);

        // O status atual continua visivel para quem encaminhou (desfecho), mesmo com o historico cortado.
        $this->actingAs($b, 'sanctum')->postJson("/api/protocolos/{$protocol->id}/receber")->assertOk();
        $vis = $this->actingAs($a, 'sanctum')->getJson("/api/protocolos/{$protocol->id}")->assertOk()->json();
        $this->assertSame('recebido', $vis['status']);

        // Quem esta com o protocolo enxerga tudo.
        $jsonB = $this->actingAs($b, 'sanctum')->getJson("/api/protocolos/{$protocol->id}")->assertOk()->json();
        $this->assertEqualsCanonicalizing(['antes', 'depois'], collect($jsonB['comments'])->pluck('conteudo')->all());

        Carbon::setTestNow();
    }

    public function test_arvore_de_unidades_inclui_todos_os_niveis(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $sec = ProtocolOrganizationalUnit::create(['tipo' => 'secretaria', 'nome' => 'Sec', 'ativo' => true]);
        $dep = ProtocolOrganizationalUnit::create(['tipo' => 'departamento', 'nome' => 'Dep', 'parent_id' => $sec->id, 'ativo' => true]);
        $sub = ProtocolOrganizationalUnit::create(['tipo' => 'subdepartamento', 'nome' => 'Sub', 'parent_id' => $dep->id, 'ativo' => true]);
        $sec2 = ProtocolOrganizationalUnit::create(['tipo' => 'secretaria', 'nome' => 'Sec Profunda', 'parent_id' => $sub->id, 'ativo' => true]);

        $tree = $this->actingAs($admin, 'sanctum')->getJson('/api/protocolos/unidades-organizacionais')->assertOk()->json();

        $flat = function (array $items) use (&$flat) {
            return collect($items)->flatMap(fn ($i) => array_merge([$i['id']], $flat($i['children'] ?? [])))->all();
        };
        $this->assertContains($sec2->id, $flat($tree));
    }

    public function test_unidade_nao_pode_ser_pai_de_si_mesma(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $sec = ProtocolOrganizationalUnit::create(['tipo' => 'secretaria', 'nome' => 'Sec', 'ativo' => true]);
        $dep = ProtocolOrganizationalUnit::create(['tipo' => 'departamento', 'nome' => 'Dep', 'parent_id' => $sec->id, 'ativo' => true]);

        $this->actingAs($admin, 'sanctum')->putJson("/api/protocolos/unidades-organizacionais/{$sec->id}", ['parent_id' => $sec->id])->assertStatus(422);
        $this->actingAs($admin, 'sanctum')->putJson("/api/protocolos/unidades-organizacionais/{$sec->id}", ['parent_id' => $dep->id])->assertStatus(422);
    }

    public function test_quem_encaminhou_acompanha_o_desfecho_mas_nao_o_restante(): void
    {
        $a = User::factory()->create(['profile' => 'user', 'active' => true]);
        $b = User::factory()->create(['profile' => 'user', 'active' => true]);
        $unit = ProtocolOrganizationalUnit::create(['tipo' => 'secretaria', 'nome' => 'Saude', 'ativo' => true]);
        $protocol = $this->makeProtocol($a, $a);

        Carbon::setTestNow('2026-02-01 10:00:00');
        $this->actingAs($a, 'sanctum')->postJson("/api/protocolos/{$protocol->id}/encaminhar", [
            'destino_unit_id' => $unit->id, 'destino_user_id' => $b->id,
        ])->assertOk();

        Carbon::setTestNow('2026-02-01 11:00:00');
        $this->actingAs($b, 'sanctum')->postJson("/api/protocolos/{$protocol->id}/comentarios", ['conteudo' => 'interno'])->assertOk();
        $this->actingAs($b, 'sanctum')->postJson("/api/protocolos/{$protocol->id}/encerrar", ['justificativa_encerramento' => 'Resolvido'])->assertOk();

        $json = $this->actingAs($a, 'sanctum')->getJson("/api/protocolos/{$protocol->id}")->assertOk()->json();
        $this->assertSame('encerrado', $json['status']);
        $this->assertSame('Resolvido', $json['justificativa_encerramento']);
        $acoes = collect($json['movements'])->pluck('acao')->all();
        $this->assertContains('encerrado', $acoes);
        $this->assertNotContains('comentado', $acoes);
        $this->assertSame([], $json['comments']);

        $historico = $this->actingAs($a, 'sanctum')->getJson("/api/protocolos/{$protocol->id}/historico")->assertOk()->json();
        $this->assertContains('encerrado', collect($historico)->pluck('acao')->all());

        Carbon::setTestNow();
    }

    public function test_listagens_informam_quantos_anexos_ativos_cada_protocolo_tem(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $com = $this->makeProtocol($admin, $admin);
        $sem = $this->makeProtocol($admin, $admin);

        foreach ([true, true, false] as $ativo) {
            ProtocolAttachment::create([
                'protocol_id' => $com->id, 'user_id' => $admin->id, 'nome_original' => 'a.pdf',
                'caminho' => 'x/a.pdf', 'mime_type' => 'application/pdf', 'tamanho_bytes' => 1, 'ativo' => $ativo,
            ]);
        }

        foreach (['/api/protocolos/caixa-entrada', '/api/protocolos'] as $uri) {
            $this->app['auth']->forgetGuards();
            $rows = collect($this->actingAs($admin, 'sanctum')->getJson($uri)->assertOk()->json('data'))->keyBy('id');
            $this->assertSame(2, (int) $rows[$com->id]['attachments_count'], $uri);
            $this->assertSame(0, (int) $rows[$sem->id]['attachments_count'], $uri);
        }
    }
}

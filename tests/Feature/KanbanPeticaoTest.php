<?php

namespace Tests\Feature;

use App\Models\Fiscalizacao;
use App\Models\KanbanTask;
use App\Models\PeticaoMotivo;
use App\Models\ProtocolOrganizationalUnit;
use App\Models\ProtocolUserUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\GrantsPages;
use Tests\TestCase;

class KanbanPeticaoTest extends TestCase
{
    use GrantsPages;
    use RefreshDatabase;

    private ProtocolOrganizationalUnit $secretaria;

    private ProtocolOrganizationalUnit $visa;

    private ProtocolOrganizationalUnit $setor;

    private ProtocolOrganizationalUnit $outra;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('private');
        $this->grantPages('user', ['/kanban']);

        $this->secretaria = $this->unidade('secretaria', 'Saúde');
        $this->visa = $this->unidade('departamento', 'Vigilância Sanitária', $this->secretaria);
        $this->setor = $this->unidade('setor', 'Setor de Alvarás', $this->visa);
        $this->outra = $this->unidade('departamento', 'Outro Departamento', $this->secretaria);
    }

    private function unidade(string $tipo, string $nome, ?ProtocolOrganizationalUnit $pai = null): ProtocolOrganizationalUnit
    {
        return ProtocolOrganizationalUnit::create([
            'parent_id' => $pai?->id, 'tipo' => $tipo, 'codigo' => strtoupper(substr(md5($nome), 0, 8)), 'nome' => $nome, 'ativo' => true,
        ]);
    }

    private function usuarioLotado(?ProtocolOrganizationalUnit $unidade, string $perfil = 'user'): User
    {
        $user = User::factory()->create(['profile' => $perfil, 'active' => true]);
        if ($unidade) {
            ProtocolUserUnit::create(['user_id' => $user->id, 'protocol_organizational_unit_id' => $unidade->id, 'papel' => 'membro', 'ativo' => true]);
        }

        return $user;
    }

    private function peticao(?PeticaoMotivo $motivo, array $extra = [])
    {
        return $this->postJson('/api/public/petitions', $extra + [
            'motivo_id' => $motivo?->id,
            'assunto' => 'Falta de higiene', 'descricao_denuncia' => 'Alimentos expostos.',
            'local_endereco' => 'Rua das Flores, 100',
            'denunciante_nome' => 'Maria Sigilosa', 'denunciante_contato' => '35 99999-0000',
        ]);
    }

    private function kanban(User $user, string $query = '')
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user, 'sanctum')->getJson('/api/kanban'.$query);
    }

    public function test_peticao_com_motivo_de_unidade_gera_card_publico_na_unidade(): void
    {
        $motivo = PeticaoMotivo::create(['nome' => 'Solicitar vistoria', 'unit_id' => $this->visa->id]);

        $protocolo = $this->peticao($motivo)->assertCreated()->json('protocolo');

        $fiscalizacao = Fiscalizacao::where('protocolo', $protocolo)->firstOrFail();
        $card = KanbanTask::where('fiscalizacao_id', $fiscalizacao->id)->firstOrFail();
        $this->assertSame($this->visa->id, $card->unit_id);
        $this->assertSame('public', $card->visibility);
        $this->assertSame('novo', $card->status);
        $this->assertStringContainsString($protocolo, $card->titulo);
        $this->assertStringContainsString('Solicitar vistoria', $card->titulo);
        $this->assertStringNotContainsString('Maria Sigilosa', (string) $card->descricao);
        $this->assertStringNotContainsString('99999-0000', (string) $card->descricao);
    }

    public function test_motivo_sem_unidade_ou_robo_nao_gera_card(): void
    {
        $semUnidade = PeticaoMotivo::create(['nome' => 'Sem unidade']);
        $this->peticao($semUnidade)->assertCreated();
        $this->peticao($semUnidade, ['website' => 'http://spam'])->assertCreated();

        $this->assertSame(0, KanbanTask::count());
    }

    public function test_lotado_na_unidade_ou_em_subunidade_ve_o_card_e_os_demais_nao(): void
    {
        $motivo = PeticaoMotivo::create(['nome' => 'Denúncia', 'unit_id' => $this->visa->id]);
        $this->peticao($motivo)->assertCreated();

        $noDepartamento = $this->usuarioLotado($this->visa);
        $noSetor = $this->usuarioLotado($this->setor);
        $emOutra = $this->usuarioLotado($this->outra);
        $semLotacao = $this->usuarioLotado(null);
        $admin = $this->usuarioLotado(null, 'admin');

        $this->kanban($noDepartamento)->assertOk()->assertJsonCount(1);
        $this->kanban($noSetor)->assertOk()->assertJsonCount(1);
        $this->kanban($admin)->assertOk()->assertJsonCount(1);
        $this->kanban($emOutra)->assertOk()->assertJsonCount(0);
        $this->kanban($semLotacao)->assertOk()->assertJsonCount(0);
        $this->kanban($noDepartamento, '?pending_only=1')->assertOk()->assertJsonCount(1);
    }

    public function test_quem_nao_e_da_unidade_nao_altera_nem_exclui_o_card(): void
    {
        $motivo = PeticaoMotivo::create(['nome' => 'Denúncia', 'unit_id' => $this->visa->id]);
        $this->peticao($motivo)->assertCreated();
        $card = KanbanTask::firstOrFail();
        $intruso = $this->usuarioLotado($this->outra);
        $membro = $this->usuarioLotado($this->visa);

        $this->app['auth']->forgetGuards();
        $this->actingAs($intruso, 'sanctum')->putJson("/api/kanban/{$card->id}", ['status' => 'em_andamento'])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->actingAs($intruso, 'sanctum')->deleteJson("/api/kanban/{$card->id}")->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->actingAs($membro, 'sanctum')->putJson("/api/kanban/{$card->id}", ['status' => 'em_andamento'])->assertOk();
        $this->assertSame('em_andamento', $card->fresh()->status);
    }

    public function test_excluir_a_fiscalizacao_remove_o_card(): void
    {
        $motivo = PeticaoMotivo::create(['nome' => 'Denúncia', 'unit_id' => $this->visa->id]);
        $protocolo = $this->peticao($motivo)->json('protocolo');

        Fiscalizacao::where('protocolo', $protocolo)->firstOrFail()->delete();

        $this->assertSame(0, KanbanTask::count());
    }

    public function test_card_expoe_o_vinculo_com_a_fiscalizacao_e_a_unidade(): void
    {
        $motivo = PeticaoMotivo::create(['nome' => 'Denúncia', 'unit_id' => $this->visa->id]);
        $protocolo = $this->peticao($motivo)->json('protocolo');
        $membro = $this->usuarioLotado($this->visa);

        $item = $this->kanban($membro)->assertOk()->json('0');

        $this->assertSame($protocolo, $item['fiscalizacao']['protocolo']);
        $this->assertSame('Vigilância Sanitária', $item['unit']['nome']);
    }
}

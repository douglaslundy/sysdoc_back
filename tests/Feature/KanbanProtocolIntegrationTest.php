<?php

namespace Tests\Feature;

use App\Models\KanbanTask;
use App\Models\User;
use App\Models\ProtocolOrganizationalUnit;
use App\Models\ProtocolUserUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KanbanProtocolIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;
    private ProtocolOrganizationalUnit $origin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);
        $this->otherUser = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);
        $this->origin = ProtocolOrganizationalUnit::create([
            'tipo' => 'secretaria',
            'nome' => 'Secretaria de Teste',
            'ativo' => true,
        ]);
        ProtocolUserUnit::create([
            'user_id' => $this->user->id,
            'protocol_organizational_unit_id' => $this->origin->id,
            'papel' => 'lotacao',
            'ativo' => true,
        ]);
    }

    public function test_criar_item_no_kanban_nao_cria_protocolo(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/kanban', [
                'titulo' => 'Acompanhar obra',
                'descricao' => 'Item independente do kanban',
                'status' => 'novo',
                'prioridade' => 'alta',
                'visibility' => 'private',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('titulo', 'Acompanhar obra')
            ->assertJsonPath('visibility', 'private');

        $this->assertDatabaseCount('kanban_tasks', 1);
        $this->assertDatabaseCount('protocols', 0);
    }

    public function test_criar_protocolo_cria_item_novo_no_kanban(): void
    {
        $this->seedProtocolType();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/protocolos', [
                'assunto' => 'Solicitar acompanhamento operacional',
                'descricao' => 'O protocolo precisa virar acompanhamento no quadro.',
                'tipo' => 'administrativo',
                'solicitante_tipo' => 'interno',
                'destino_user_id' => $this->user->id,
            ]);

        $response->assertStatus(201);

        $protocolId = $response->json('id');
        $this->assertNotNull($protocolId);

        $this->assertDatabaseHas('protocols', [
            'id' => $protocolId,
            'assunto' => 'Solicitar acompanhamento operacional',
            'criado_por_id' => $this->user->id,
            'responsavel_atual_id' => $this->user->id,
            'origem_unit_id' => $this->origin->id,
            'solicitante_nome' => $this->user->name,
        ]);

        $this->assertDatabaseHas('kanban_tasks', [
            'protocol_id' => $protocolId,
            'titulo' => 'Solicitar acompanhamento operacional',
            'status' => 'novo',
            'visibility' => 'public',
        ]);
    }

    public function test_movimentar_protocolo_no_kanban_atualiza_protocolo_e_historico(): void
    {
        $this->seedProtocolType();
        $protocolId = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/protocolos', [
                'assunto' => 'Fluxo pelo Kanban',
                'tipo' => 'administrativo',
                'destino_user_id' => $this->user->id,
            ])
            ->assertCreated()
            ->json('id');

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/protocolos/{$protocolId}/kanban-status", [
                'kanban_status' => 'em_andamento',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'em_andamento')
            ->assertJsonPath('kanban_task.status', 'em_andamento');

        $this->assertDatabaseHas('protocol_movements', [
            'protocol_id' => $protocolId,
            'acao' => 'movido_no_kanban',
            'status_novo' => 'em_andamento',
        ]);
    }

    public function test_protocolo_pode_vincular_item_kanban_existente(): void
    {
        $this->seedProtocolType();

        $task = KanbanTask::create([
            'titulo' => 'Tarefa manual',
            'descricao' => 'Criada diretamente no kanban',
            'status' => 'novo',
            'prioridade' => 'normal',
            'created_by_id' => $this->user->id,
            'updated_by_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/protocolos', [
                'assunto' => 'Vincular tarefa existente',
                'descricao' => 'Deve apenas vincular o item do kanban.',
                'tipo' => 'administrativo',
                'solicitante_tipo' => 'interno',
                'destino_user_id' => $this->user->id,
                'kanban' => [
                    'ativar' => true,
                    'id' => $task->id,
                ],
            ]);

        $response->assertStatus(201);

        $protocolId = $response->json('id');
        $this->assertNotNull($protocolId);

        $this->assertDatabaseHas('kanban_tasks', [
            'id' => $task->id,
            'protocol_id' => $protocolId,
        ]);
    }

    public function test_item_privado_do_kanban_aparece_apenas_para_o_criador(): void
    {
        KanbanTask::create([
            'titulo' => 'Privado',
            'descricao' => 'Visível apenas para o autor',
            'status' => 'novo',
            'prioridade' => 'normal',
            'visibility' => 'private',
            'created_by_id' => $this->user->id,
            'updated_by_id' => $this->user->id,
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/kanban')
            ->assertStatus(200)
            ->assertJsonFragment(['titulo' => 'Privado']);

        $this->actingAs($this->otherUser, 'sanctum')
            ->getJson('/api/kanban')
            ->assertStatus(200)
            ->assertJsonMissing(['titulo' => 'Privado']);
    }

    private function seedProtocolType(): void
    {
        DB::table('protocol_types')->updateOrInsert(
            ['codigo' => 'administrativo'],
            [
                'nome' => 'Administrativo',
                'descricao' => 'Teste',
                'ordem' => 1,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

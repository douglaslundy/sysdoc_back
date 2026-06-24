<?php

namespace Tests\Feature;

use App\Models\KanbanTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KanbanProtocolIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
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
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('titulo', 'Acompanhar obra');

        $this->assertDatabaseCount('kanban_tasks', 1);
        $this->assertDatabaseCount('protocols', 0);
    }

    public function test_protocolo_pode_criar_item_kanban_quando_houver_regra_explicita(): void
    {
        $this->seedProtocolType();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/protocolos', [
                'assunto' => 'Solicitar acompanhamento operacional',
                'descricao' => 'O protocolo precisa virar acompanhamento no quadro.',
                'tipo' => 'administrativo',
                'solicitante_tipo' => 'interno',
                'kanban' => [
                    'ativar' => true,
                    'titulo' => 'Acompanhamento do protocolo',
                    'descricao' => 'Criado a partir do protocolo',
                    'status' => 'em_andamento',
                    'prioridade' => 'urgente',
                ],
            ]);

        $response->assertStatus(201);

        $protocolId = $response->json('id');
        $this->assertNotNull($protocolId);

        $this->assertDatabaseHas('protocols', [
            'id' => $protocolId,
            'assunto' => 'Solicitar acompanhamento operacional',
        ]);

        $this->assertDatabaseHas('kanban_tasks', [
            'protocol_id' => $protocolId,
            'titulo' => 'Acompanhamento do protocolo',
            'status' => 'em_andamento',
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

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
        ]);

        $this->assertDatabaseHas('kanban_tasks', [
            'protocol_id' => $protocolId,
            'titulo' => 'Solicitar acompanhamento operacional',
            'status' => 'novo',
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

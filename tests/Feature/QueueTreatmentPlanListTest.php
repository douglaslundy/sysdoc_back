<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Queue;
use App\Models\QueueTreatmentPlan;
use App\Models\QueueTreatmentSession;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueTreatmentPlanListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Speciality $fisio;

    private Client $client;

    private Queue $queue;

    private QueueTreatmentPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->fisio = Speciality::create([
            'id_user' => $this->admin->id,
            'name' => 'Fisioterapia',
            'allows_session_scheduling' => true,
        ]);
        $this->client = Client::create([
            'name' => 'Paciente Lista',
            'mother' => 'Mae Teste',
            'cpf' => '999.888.777-66',
            'born_date' => '1990-01-01',
            'active' => true,
        ]);
        $this->queue = Queue::create([
            'id_client' => $this->client->id,
            'id_specialities' => $this->fisio->id,
            'id_user' => $this->admin->id,
            'done' => true,
            'urgency' => false,
        ]);
        $this->plan = QueueTreatmentPlan::create([
            'queue_id' => $this->queue->id,
            'speciality_id' => $this->fisio->id,
            'client_id' => $this->client->id,
            'created_by_user_id' => $this->admin->id,
            'total_sessions' => 1,
            'weekdays' => [1],
            'started_at' => '2026-09-07',
            'expected_end_at' => '2026-09-07',
            'status' => 'active',
        ]);
        QueueTreatmentSession::create([
            'treatment_plan_id' => $this->plan->id,
            'scheduled_date' => '2026-09-07',
            'status' => 'pending',
        ]);
    }

    public function test_lista_planos_ativos_com_nome_do_paciente_e_especialidade(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/queue-treatment-plans?status=active');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $this->plan->id,
            'client_name' => 'Paciente Lista',
            'speciality_name' => 'Fisioterapia',
        ]);
    }

    public function test_mostra_um_plano_especifico_com_sessoes(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/queue-treatment-plans/{$this->plan->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'sessions');
    }

    public function test_retorna_plano_pelo_id_da_fila_original(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/queues/{$this->queue->id}/treatment-plan");

        $response->assertOk();
        $response->assertJsonPath('id', $this->plan->id);
    }

    public function test_retorna_null_quando_a_fila_nao_tem_plano(): void
    {
        $outraFila = Queue::create([
            'id_client' => $this->client->id,
            'id_specialities' => $this->fisio->id,
            'id_user' => $this->admin->id,
            'done' => false,
            'urgency' => false,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/queues/{$outraFila->id}/treatment-plan");

        $response->assertOk();
        $response->assertJson(['plan' => null]);
    }
}

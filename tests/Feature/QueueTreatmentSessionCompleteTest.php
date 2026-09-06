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

class QueueTreatmentSessionCompleteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private QueueTreatmentPlan $plan;

    private QueueTreatmentSession $session1;

    private QueueTreatmentSession $session2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $speciality = Speciality::create([
            'id_user' => $this->admin->id,
            'name' => 'Fisioterapia',
            'allows_session_scheduling' => true,
        ]);
        $client = Client::create(['name' => 'Paciente', 'mother' => 'Mae', 'cpf' => '222.222.222-22', 'born_date' => '1990-01-01', 'active' => true]);
        $queue = Queue::create([
            'id_client' => $client->id,
            'id_specialities' => $speciality->id,
            'id_user' => $this->admin->id,
            'done' => true,
            'urgency' => false,
        ]);
        $this->plan = QueueTreatmentPlan::create([
            'queue_id' => $queue->id,
            'speciality_id' => $speciality->id,
            'client_id' => $client->id,
            'created_by_user_id' => $this->admin->id,
            'total_sessions' => 2,
            'weekdays' => [1],
            'started_at' => '2026-09-07',
            'expected_end_at' => '2026-09-14',
            'status' => 'active',
        ]);
        $this->session1 = QueueTreatmentSession::create([
            'treatment_plan_id' => $this->plan->id,
            'scheduled_date' => '2026-09-07',
            'status' => 'pending',
        ]);
        $this->session2 = QueueTreatmentSession::create([
            'treatment_plan_id' => $this->plan->id,
            'scheduled_date' => '2026-09-14',
            'status' => 'pending',
        ]);
    }

    public function test_concluir_sessao_marca_como_done(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-sessions/{$this->session1->id}/complete");

        $response->assertOk();
        $this->assertDatabaseHas('queue_treatment_sessions', [
            'id' => $this->session1->id,
            'status' => 'done',
        ]);
        $this->assertDatabaseHas('queue_treatment_plans', [
            'id' => $this->plan->id,
            'status' => 'active',
        ]);
    }

    public function test_concluir_a_ultima_sessao_conclui_o_plano(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-sessions/{$this->session1->id}/complete")
            ->assertOk();

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-sessions/{$this->session2->id}/complete")
            ->assertOk();

        $this->assertDatabaseHas('queue_treatment_plans', [
            'id' => $this->plan->id,
            'status' => 'completed',
        ]);
    }

    public function test_concluir_sem_can_edit_retorna_403(): void
    {
        $limited = User::factory()->create(['profile' => 'user', 'active' => true]);

        $this->actingAs($limited, 'sanctum')
            ->putJson("/api/queue-treatment-sessions/{$this->session1->id}/complete")
            ->assertStatus(403);
    }

    public function test_concluir_sessao_de_plano_cancelado_retorna_422_e_nao_reabre_o_plano(): void
    {
        $this->plan->update(['status' => 'cancelled']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-sessions/{$this->session1->id}/complete");

        $response->assertStatus(422);

        $this->assertDatabaseHas('queue_treatment_sessions', [
            'id' => $this->session1->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('queue_treatment_plans', [
            'id' => $this->plan->id,
            'status' => 'cancelled',
        ]);
    }
}

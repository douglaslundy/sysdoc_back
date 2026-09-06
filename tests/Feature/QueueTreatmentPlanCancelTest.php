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

class QueueTreatmentPlanCancelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Queue $queue;

    private QueueTreatmentPlan $plan;

    private QueueTreatmentSession $pendingSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $speciality = Speciality::create([
            'id_user' => $this->admin->id,
            'name' => 'Fisioterapia',
            'allows_session_scheduling' => true,
        ]);
        $client = Client::create(['name' => 'Paciente', 'mother' => 'Mae', 'cpf' => '333.333.333-33', 'active' => true, 'born_date' => '1990-01-01']);
        $this->queue = Queue::create([
            'id_client' => $client->id,
            'id_specialities' => $speciality->id,
            'id_user' => $this->admin->id,
            'done' => true,
            'date_of_realized' => now()->toDateString(),
            'urgency' => false,
        ]);
        $this->plan = QueueTreatmentPlan::create([
            'queue_id' => $this->queue->id,
            'speciality_id' => $speciality->id,
            'client_id' => $client->id,
            'created_by_user_id' => $this->admin->id,
            'total_sessions' => 2,
            'weekdays' => [1],
            'started_at' => '2026-09-07',
            'expected_end_at' => '2026-09-14',
            'status' => 'active',
        ]);
        $this->pendingSession = QueueTreatmentSession::create([
            'treatment_plan_id' => $this->plan->id,
            'scheduled_date' => '2026-09-14',
            'status' => 'pending',
        ]);
    }

    public function test_admin_cancela_plano_e_paciente_volta_para_fila(): void
    {
        $originalCreatedAt = $this->queue->created_at;

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-plans/{$this->plan->id}/cancel", [
                'reason' => 'Paciente solicitou reagendamento manual com outro profissional',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('queue_treatment_plans', [
            'id' => $this->plan->id,
            'status' => 'cancelled',
            'cancelled_by_user_id' => $this->admin->id,
            'cancelled_reason' => 'Paciente solicitou reagendamento manual com outro profissional',
        ]);

        $this->assertDatabaseHas('queue_treatment_sessions', [
            'id' => $this->pendingSession->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('queue', [
            'id' => $this->queue->id,
            'done' => 0,
            'date_of_realized' => null,
        ]);

        $this->queue->refresh();
        $this->assertTrue($this->queue->created_at->equalTo($originalCreatedAt));
    }

    public function test_cancelar_exige_justificativa_de_pelo_menos_10_caracteres(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-plans/{$this->plan->id}/cancel", [
                'reason' => 'curto',
            ]);

        $response->assertStatus(422);
    }

    public function test_usuario_nao_admin_recebe_403(): void
    {
        $user = User::factory()->create(['profile' => 'user', 'active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/queue-treatment-plans/{$this->plan->id}/cancel", [
                'reason' => 'Tentando cancelar sem ser admin',
            ]);

        $response->assertStatus(403);
    }

    public function test_cancelamento_e_registrado_na_auditoria(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-plans/{$this->plan->id}/cancel", [
                'reason' => 'Motivo de teste para auditoria completa',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'RETURN_TO_QUEUE',
            'user_id' => $this->admin->id,
        ]);
    }
}

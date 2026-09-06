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

class QueueTreatmentSessionRescheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private QueueTreatmentPlan $plan;

    private QueueTreatmentSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $speciality = Speciality::create([
            'id_user' => $this->admin->id,
            'name' => 'Fisioterapia',
            'allows_session_scheduling' => true,
        ]);
        $client = Client::create(['name' => 'Paciente', 'mother' => 'Mae', 'cpf' => '111.111.111-11', 'born_date' => '1990-01-01', 'active' => true]);
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
        $this->session = QueueTreatmentSession::create([
            'treatment_plan_id' => $this->plan->id,
            'scheduled_date' => '2026-09-07',
            'status' => 'pending',
        ]);
        QueueTreatmentSession::create([
            'treatment_plan_id' => $this->plan->id,
            'scheduled_date' => '2026-09-14',
            'status' => 'pending',
        ]);
    }

    public function test_adiar_sessao_empurra_prazo_final_do_plano_na_mesma_medida(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-sessions/{$this->session->id}/reschedule", [
                'new_date' => '2026-09-09',
                'reason' => 'Profissional em licença médica',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('queue_treatment_sessions', [
            'id' => $this->session->id,
            'scheduled_date' => '2026-09-09',
            'original_scheduled_date' => '2026-09-07',
            'status' => 'rescheduled_pending',
            'reschedule_reason' => 'Profissional em licença médica',
        ]);

        // Atrasou 2 dias (07 -> 09), então o prazo final (14) empurra pra 16.
        $this->assertDatabaseHas('queue_treatment_plans', [
            'id' => $this->plan->id,
            'expected_end_at' => '2026-09-16',
        ]);
    }

    public function test_adiar_de_novo_preserva_a_data_original(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-sessions/{$this->session->id}/reschedule", [
                'new_date' => '2026-09-09',
                'reason' => 'Primeiro adiamento',
            ]);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-sessions/{$this->session->id}/reschedule", [
                'new_date' => '2026-09-10',
                'reason' => 'Segundo adiamento',
            ]);

        $this->assertDatabaseHas('queue_treatment_sessions', [
            'id' => $this->session->id,
            'scheduled_date' => '2026-09-10',
            'original_scheduled_date' => '2026-09-07',
        ]);
    }

    public function test_adiar_sem_motivo_falha(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-sessions/{$this->session->id}/reschedule", [
                'new_date' => '2026-09-09',
            ]);

        $response->assertStatus(422);
    }

    public function test_adiar_sem_can_edit_retorna_403(): void
    {
        $limited = User::factory()->create(['profile' => 'user', 'active' => true]);

        $response = $this->actingAs($limited, 'sanctum')
            ->putJson("/api/queue-treatment-sessions/{$this->session->id}/reschedule", [
                'new_date' => '2026-09-09',
                'reason' => 'Teste',
            ]);

        $response->assertStatus(403);
    }

    public function test_adiar_sessao_ja_concluida_retorna_422_e_nao_altera_nada(): void
    {
        $this->session->update(['status' => 'done']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-sessions/{$this->session->id}/reschedule", [
                'new_date' => '2026-09-09',
                'reason' => 'Tentativa de reagendar sessao ja concluida',
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('queue_treatment_sessions', [
            'id' => $this->session->id,
            'status' => 'done',
            'scheduled_date' => '2026-09-07',
        ]);
    }
}

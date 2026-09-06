<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Queue;
use App\Models\QueueTreatmentPlan;
use App\Models\QueueTreatmentSession;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public function test_cancelar_plano_ja_finalizado_retorna_422_e_nao_altera_a_fila(): void
    {
        $this->plan->update(['status' => 'completed']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-plans/{$this->plan->id}/cancel", [
                'reason' => 'Tentativa de cancelar um plano ja concluido',
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('queue_treatment_plans', [
            'id' => $this->plan->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('queue', [
            'id' => $this->queue->id,
            'done' => 1,
        ]);
    }

    public function test_paciente_volta_para_a_posicao_correta_na_fila_ao_cancelar(): void
    {
        $originalCreatedAt = $this->queue->created_at->copy();

        // Paciente que entrou na fila ANTES do paciente cancelado (deve continuar na frente).
        DB::table('queue')->insert([
            'uuid' => (string) Str::uuid(),
            'id_client' => $this->queue->id_client,
            'id_specialities' => $this->queue->id_specialities,
            'id_user' => $this->admin->id,
            'done' => false,
            'urgency' => false,
            'created_at' => $originalCreatedAt->copy()->subMinutes(10),
            'updated_at' => $originalCreatedAt->copy()->subMinutes(10),
        ]);

        // Paciente que entrou na fila DEPOIS do paciente cancelado (deve continuar atras).
        DB::table('queue')->insert([
            'uuid' => (string) Str::uuid(),
            'id_client' => $this->queue->id_client,
            'id_specialities' => $this->queue->id_specialities,
            'id_user' => $this->admin->id,
            'done' => false,
            'urgency' => false,
            'created_at' => $originalCreatedAt->copy()->addMinutes(10),
            'updated_at' => $originalCreatedAt->copy()->addMinutes(10),
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queue-treatment-plans/{$this->plan->id}/cancel", [
                'reason' => 'Paciente solicitou reagendamento manual com outro profissional',
            ])
            ->assertOk();

        $response = $this->actingAs($this->admin, 'sanctum')->getJson(
            "/api/queues?speciality_id={$this->queue->id_specialities}&done=0&urgency=0&per_page=50"
        );

        $response->assertOk();

        $entry = collect($response->json('data'))->firstWhere('id', $this->queue->id);
        $this->assertNotNull($entry, 'Fila com o paciente cancelado nao encontrada na listagem.');
        $this->assertSame(2, $entry['position']);
    }
}

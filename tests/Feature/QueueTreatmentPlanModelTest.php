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

class QueueTreatmentPlanModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_plano_com_sessoes_e_navega_relacionamentos(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $speciality = Speciality::create([
            'id_user' => $admin->id,
            'name' => 'Fisioterapia',
            'allows_session_scheduling' => true,
        ]);
        $client = Client::create([
            'name' => 'Paciente Teste',
            'mother' => 'Mae Teste',
            'cpf' => '111.222.333-44',
            'born_date' => '1990-01-01',
            'active' => true,
        ]);
        $queue = Queue::create([
            'id_client' => $client->id,
            'id_specialities' => $speciality->id,
            'id_user' => $admin->id,
            'done' => false,
            'urgency' => false,
        ]);

        $plan = QueueTreatmentPlan::create([
            'queue_id' => $queue->id,
            'speciality_id' => $speciality->id,
            'client_id' => $client->id,
            'created_by_user_id' => $admin->id,
            'total_sessions' => 2,
            'weekdays' => [1, 5],
            'started_at' => '2026-09-07',
            'expected_end_at' => '2026-09-11',
            'status' => 'active',
        ]);

        $session1 = QueueTreatmentSession::create([
            'treatment_plan_id' => $plan->id,
            'scheduled_date' => '2026-09-07',
            'status' => 'pending',
        ]);
        $session2 = QueueTreatmentSession::create([
            'treatment_plan_id' => $plan->id,
            'scheduled_date' => '2026-09-11',
            'status' => 'pending',
        ]);

        $this->assertSame([1, 5], $plan->fresh()->weekdays);
        $this->assertCount(2, $plan->sessions);
        $this->assertTrue($plan->queue->is($queue));
        $this->assertTrue($plan->speciality->is($speciality));
        $this->assertTrue($plan->client->is($client));
        $this->assertTrue($plan->createdBy->is($admin));
        $this->assertTrue($session1->plan->is($plan));
        $this->assertTrue($session2->plan->is($plan));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Speciality;
use App\Models\User;
use App\Models\UserSpecialityPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class QueueTreatmentPlanCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Speciality $fisio;

    private Client $client;

    private int $queueId;

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
            'name' => 'Paciente Teste',
            'mother' => 'Mae Teste',
            'cpf' => '555.666.777-88',
            'born_date' => '1990-01-01',
            'active' => true,
        ]);

        DB::table('queue')->insert([
            'uuid' => (string) Str::uuid(),
            'id_client' => $this->client->id,
            'id_specialities' => $this->fisio->id,
            'id_user' => $this->admin->id,
            'done' => false,
            'urgency' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->queueId = (int) DB::table('queue')->max('id');
    }

    public function test_preview_retorna_datas_sem_gravar_nada(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/queue-treatment-plans/preview', [
            'speciality_id' => $this->fisio->id,
            'weekdays' => [1, 5],
            'total_sessions' => 3,
        ]);

        $response->assertOk();
        $this->assertCount(3, $response->json('dates'));
        $this->assertDatabaseCount('queue_treatment_plans', 0);
        $this->assertDatabaseCount('queue_treatment_sessions', 0);
    }

    public function test_criar_plano_gera_sessoes_e_conclui_a_fila(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/queue-treatment-plans', [
            'queue_id' => $this->queueId,
            'weekdays' => [1, 5],
            'total_sessions' => 2,
        ]);

        $response->assertStatus(201);
        $response->assertJsonCount(2, 'sessions');

        $this->assertDatabaseHas('queue_treatment_plans', [
            'queue_id' => $this->queueId,
            'speciality_id' => $this->fisio->id,
            'client_id' => $this->client->id,
            'total_sessions' => 2,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('queue', [
            'id' => $this->queueId,
            'done' => 1,
        ]);
    }

    public function test_criar_plano_em_especialidade_sem_flag_falha(): void
    {
        $outraEspecialidade = Speciality::create(['id_user' => $this->admin->id, 'name' => 'Cardiologia']);
        DB::table('queue')->insert([
            'uuid' => (string) Str::uuid(),
            'id_client' => $this->client->id,
            'id_specialities' => $outraEspecialidade->id,
            'id_user' => $this->admin->id,
            'done' => false,
            'urgency' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $outroQueueId = (int) DB::table('queue')->max('id');

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/queue-treatment-plans', [
            'queue_id' => $outroQueueId,
            'weekdays' => [1],
            'total_sessions' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_criar_plano_sem_can_insert_retorna_403(): void
    {
        $limited = User::factory()->create(['profile' => 'user', 'active' => true]);
        // Sem nenhuma linha em user_speciality_permissions: sem can_insert.

        $response = $this->actingAs($limited, 'sanctum')->postJson('/api/queue-treatment-plans', [
            'queue_id' => $this->queueId,
            'weekdays' => [1],
            'total_sessions' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_criar_plano_com_can_insert_funciona(): void
    {
        $limited = User::factory()->create(['profile' => 'user', 'active' => true]);
        UserSpecialityPermission::create([
            'user_id' => $limited->id,
            'speciality_id' => $this->fisio->id,
            'can_view' => true,
            'can_edit' => false,
            'can_insert' => true,
        ]);

        $response = $this->actingAs($limited, 'sanctum')->postJson('/api/queue-treatment-plans', [
            'queue_id' => $this->queueId,
            'weekdays' => [1],
            'total_sessions' => 1,
        ]);

        $response->assertStatus(201);
    }
}

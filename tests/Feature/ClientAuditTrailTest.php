<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Queue;
use App\Models\Speciality;
use App\Models\Trip;
use App\Models\TripClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->client = Client::create([
            'name' => 'Cidadao Auditado', 'mother' => 'Mae', 'cpf' => '111.222.333-44',
            'born_date' => '1990-01-01', 'active' => true,
        ]);
        AuditLog::query()->delete(); // ignora a criacao do cidadao no setUp
    }

    private function trip(): Trip
    {
        $route = DB::table('routes')->insertGetId([
            'id_user' => $this->admin->id, 'origin' => 'A', 'origin_state' => 'MG', 'destination' => 'B',
            'destination_state' => 'MG', 'distance' => 10, 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $vehicle = DB::table('vehicles')->insertGetId([
            'id_user' => $this->admin->id, 'brand' => 'M', 'model' => 'M', 'color' => 'B', 'license_plate' => 'ABC1234',
            'renavan' => '12345678901', 'chassis' => '1234567890ABCDEFG', 'capacity' => 10, 'year' => 2020,
            'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Trip::create([
            'user_id' => $this->admin->id, 'driver_id' => $this->admin->id, 'route_id' => $route,
            'vehicle_id' => $vehicle, 'departure_time' => '08:00:00', 'departure_date' => now()->toDateString(),
        ]);
    }

    private function logsOf(string $modelType, string $action)
    {
        return AuditLog::where('client_id', $this->client->id)->where('model_type', $modelType)->where('action', $action);
    }

    public function test_abrir_o_cadastro_varias_vezes_grava_uma_visualizacao(): void
    {
        foreach (range(1, 3) as $ignored) {
            $this->app['auth']->forgetGuards();
            $this->actingAs($this->admin, 'sanctum')->getJson("/api/clients/{$this->client->id}")->assertOk();
        }

        $this->assertSame(1, $this->logsOf('Client', 'VIEW')->count());
    }

    public function test_edicao_do_cadastro_fica_ligada_ao_cidadao(): void
    {
        $this->client->update(['name' => 'Cidadao Auditado 2']);

        $this->assertSame(1, $this->logsOf('Client', 'UPDATE')->count());
    }

    public function test_passageiro_em_viagem_e_auditado_ao_inserir_confirmar_e_remover(): void
    {
        $trip = $this->trip();

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/trip-clients', [
            'trip_id' => $trip->id, 'client_id' => $this->client->id, 'person_type' => 'passenger',
            'phone' => '35999998888', 'departure_location' => 'X', 'destination_location' => 'Y', 'time' => '08:00',
        ])->assertCreated();
        $this->assertSame(1, $this->logsOf('TripClient', 'CREATE')->count());

        $tripClientId = TripClient::where('client_id', $this->client->id)->value('id');

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')->patchJson("/api/confirm-trip-client/{$tripClientId}")->assertOk();
        $this->assertSame(1, $this->logsOf('TripClient', 'UPDATE')->count());

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/trip-clients/{$tripClientId}")->assertStatus(201);
        $this->assertSame(1, $this->logsOf('TripClient', 'DELETE')->count());
    }

    public function test_fila_e_baixa_ficam_ligadas_ao_cidadao(): void
    {
        $speciality = Speciality::create(['id_user' => $this->admin->id, 'name' => 'Fisioterapia']);

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/queues', [
            'id_client' => $this->client->id, 'id_specialities' => $speciality->id,
            'id_user' => $this->admin->id, 'urgency' => false,
        ])->assertCreated();
        $this->assertSame(1, $this->logsOf('Queue', 'CREATE')->count());

        $queueId = Queue::where('id_client', $this->client->id)->value('id');

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')->putJson("/api/queues/{$queueId}", ['done' => true])->assertOk();
        $this->assertSame(1, $this->logsOf('Queue', 'UPDATE')->count());
    }
}

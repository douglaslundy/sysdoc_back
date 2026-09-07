<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Trip;
use App\Models\TripClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TripReplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    private function createTripWithClients(): Trip
    {
        $rota = DB::table('routes')->insertGetId([
            'id_user' => $this->admin->id,
            'origin' => 'Origem Teste',
            'origin_state' => 'MG',
            'destination' => 'Destino Teste',
            'destination_state' => 'MG',
            'distance' => 10,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $veiculo = DB::table('vehicles')->insertGetId([
            'id_user' => $this->admin->id,
            'brand' => 'Marca Teste',
            'model' => 'Modelo Teste',
            'color' => 'Branco',
            'license_plate' => 'ABC1234',
            'renavan' => '12345678901',
            'chassis' => '1234567890ABCDEFG',
            'capacity' => 10,
            'year' => 2020,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $trip = Trip::create([
            'user_id' => $this->admin->id,
            'driver_id' => $this->admin->id,
            'route_id' => $rota,
            'vehicle_id' => $veiculo,
            'departure_time' => '08:00:00',
            'departure_date' => now()->toDateString(),
            'obs' => 'Observacao original',
        ]);

        $client = Client::create([
            'name' => 'Paciente Teste',
            'mother' => 'Mae Teste',
            'cpf' => '111.222.333-44',
            'active' => true,
            'born_date' => '1990-01-01',
        ]);

        TripClient::create([
            'trip_id' => $trip->id,
            'client_id' => $client->id,
            'person_type' => 'passenger',
            'phone' => '35999998888',
            'departure_location' => 'Saida Teste',
            'destination_location' => 'Destino Teste',
            'time' => '08:00',
            'is_confirmed' => true,
        ]);

        return $trip;
    }

    public function test_replicar_viagem_cria_uma_viagem_por_data_copiando_rota_obs_e_pacientes(): void
    {
        $trip = $this->createTripWithClients();
        $date1 = now()->addDays(3)->toDateString();
        $date2 = now()->addDays(10)->toDateString();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/replicate", [
                'dates' => [$date1, $date2],
            ]);

        $response->assertCreated();
        $response->assertJsonCount(2, 'trips');

        $this->assertDatabaseCount('trips', 3);

        $novasViagens = Trip::where('id', '!=', $trip->id)->get();
        $this->assertCount(2, $novasViagens);

        foreach ($novasViagens as $novaViagem) {
            $this->assertEquals($trip->route_id, $novaViagem->route_id);
            $this->assertEquals('Observacao original', $novaViagem->obs);
            $this->assertNull($novaViagem->driver_id);
            $this->assertNull($novaViagem->vehicle_id);

            $this->assertDatabaseHas('trip_clients', [
                'trip_id' => $novaViagem->id,
                'person_type' => 'passenger',
                'phone' => '35999998888',
                'is_confirmed' => false,
            ]);
        }

        $datasGeradas = $novasViagens->pluck('departure_date')
            ->map(fn ($d) => (string) $d)
            ->sort()
            ->values()
            ->all();
        $this->assertEquals([$date1, $date2], $datasGeradas);
    }

    public function test_replicar_viagem_grava_audit_log_de_create_para_cada_viagem_nova(): void
    {
        $trip = $this->createTripWithClients();
        $date = now()->addDays(3)->toDateString();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/replicate", ['dates' => [$date]])
            ->assertCreated();

        $novaViagem = Trip::where('id', '!=', $trip->id)->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CREATE',
            'model_type' => 'Trip',
            'model_id' => $novaViagem->id,
        ]);
    }

    public function test_replicar_viagem_rejeita_data_passada(): void
    {
        $trip = $this->createTripWithClients();
        $dataPassada = now()->subDay()->toDateString();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/replicate", ['dates' => [$dataPassada]]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['dates.0']);
    }

    public function test_replicar_viagem_rejeita_lista_de_datas_vazia(): void
    {
        $trip = $this->createTripWithClients();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/replicate", ['dates' => []]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['dates']);
    }

    public function test_replicar_viagem_404_para_viagem_inexistente(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/trips/999999/replicate', ['dates' => [now()->addDay()->toDateString()]]);

        $response->assertStatus(404);
    }
}

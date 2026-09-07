<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardFilaTfdFieldNamesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    public function test_dashboard_fila_retorna_total_na_fila(): void
    {
        $client = Client::create([
            'name' => 'Paciente Teste',
            'mother' => 'Mae Teste',
            'cpf' => '111.222.333-44',
            'active' => true,
            'born_date' => '1990-01-01',
        ]);
        $speciality = Speciality::create(['id_user' => $this->admin->id, 'name' => 'Clinica Geral']);

        DB::table('queue')->insert([
            'uuid' => (string) Str::uuid(),
            'id_client' => $client->id,
            'id_specialities' => $speciality->id,
            'id_user' => $this->admin->id,
            'done' => false,
            'urgency' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/dashboard/fila');

        $response->assertOk();
        $response->assertJsonPath('totais.total_na_fila', 1);
        $this->assertArrayNotHasKey('total_fila', $response->json('totais'));
    }

    public function test_dashboard_tfd_retorna_pessoas_transportadas_mes(): void
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
        $tripId = DB::table('trips')->insertGetId([
            'user_id' => $this->admin->id,
            'driver_id' => $this->admin->id,
            'route_id' => $rota,
            'vehicle_id' => $veiculo,
            'departure_time' => now(),
            'departure_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $client = Client::create([
            'name' => 'Paciente TFD',
            'mother' => 'Mae Teste',
            'cpf' => '555.666.777-88',
            'active' => true,
            'born_date' => '1990-01-01',
        ]);
        DB::table('trip_clients')->insert([
            'trip_id' => $tripId,
            'client_id' => $client->id,
            'time' => '08:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/dashboard/tfd');

        $response->assertOk();
        $response->assertJsonPath('totais.pessoas_transportadas_mes', 1);
        $this->assertArrayNotHasKey('pessoas_transportadas', $response->json('totais'));
    }
}

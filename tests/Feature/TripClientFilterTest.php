<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Trip;
use App\Models\TripClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TripClientFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    private function createTripForClient(Client $client, string $obs): Trip
    {
        static $sequence = 0;
        $sequence++;

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
            'license_plate' => "ABC{$sequence}234",
            'renavan' => "1234567890{$sequence}",
            'chassis' => "1234567890ABCDEF{$sequence}",
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
            'obs' => $obs,
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

    public function test_filtro_por_client_id_retorna_apenas_as_viagens_desse_cliente(): void
    {
        $clienteAlvo = Client::create([
            'name' => 'Paciente Alvo',
            'mother' => 'Mae Teste',
            'cpf' => '111.222.333-44',
            'active' => true,
            'born_date' => '1990-01-01',
        ]);
        $outroCliente = Client::create([
            'name' => 'Outro Paciente',
            'mother' => 'Mae Teste',
            'cpf' => '555.666.777-88',
            'active' => true,
            'born_date' => '1991-02-02',
        ]);

        $viagemDoAlvo = $this->createTripForClient($clienteAlvo, 'Viagem do cliente alvo');
        $this->createTripForClient($outroCliente, 'Viagem de outro cliente');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/trips?client_id={$clienteAlvo->id}");

        $response->assertOk();
        $ids = collect($response->json())->pluck('id')->all();

        $this->assertEquals([$viagemDoAlvo->id], $ids);
    }

    public function test_sem_filtro_client_id_continua_retornando_todas_as_viagens(): void
    {
        $clienteA = Client::create([
            'name' => 'Cliente A',
            'mother' => 'Mae Teste',
            'cpf' => '222.333.444-55',
            'active' => true,
            'born_date' => '1990-01-01',
        ]);
        $clienteB = Client::create([
            'name' => 'Cliente B',
            'mother' => 'Mae Teste',
            'cpf' => '666.777.888-99',
            'active' => true,
            'born_date' => '1991-02-02',
        ]);

        $this->createTripForClient($clienteA, 'Viagem A');
        $this->createTripForClient($clienteB, 'Viagem B');

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/trips');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }
}

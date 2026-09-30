<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\QRCodeLog;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\GrantsPages;
use Tests\TestCase;

class OptionalPaginationTest extends TestCase
{
    use GrantsPages;
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grantPages('user', ['/qrcodelogs', '/trips', '/letters']);
        $this->user = User::factory()->create(['profile' => 'user', 'active' => true]);
    }

    private function logs(int $quantidade): void
    {
        for ($i = 1; $i <= $quantidade; $i++) {
            QRCodeLog::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'position' => $i,
                'host_name' => 'host',
                'accessed_at' => now()->subMinutes($quantidade - $i),
            ]);
        }
    }

    private function viagem(string $data): Trip
    {
        static $n = 0;
        $n++;

        $rota = DB::table('routes')->insertGetId([
            'id_user' => $this->user->id, 'origin' => 'Origem', 'origin_state' => 'MG',
            'destination' => 'Destino', 'destination_state' => 'MG', 'distance' => 10,
            'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $veiculo = DB::table('vehicles')->insertGetId([
            'id_user' => $this->user->id, 'brand' => 'Marca', 'model' => 'Modelo', 'color' => 'Branco',
            'license_plate' => "ABC{$n}234", 'renavan' => "1234567890{$n}", 'chassis' => "1234567890ABCDEF{$n}",
            'capacity' => 10, 'year' => 2020, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Trip::create([
            'user_id' => $this->user->id, 'driver_id' => $this->user->id, 'route_id' => $rota,
            'vehicle_id' => $veiculo, 'departure_time' => '08:00:00', 'departure_date' => $data,
        ]);
    }

    public function test_qrcode_logs_com_page_devolve_pagina_e_total(): void
    {
        $this->logs(12);

        $resposta = $this->actingAs($this->user, 'sanctum')->getJson('/api/qrcode-logs?page=2&per_page=5');

        $resposta->assertOk()
            ->assertJsonPath('total', 12)
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(5, 'data');
    }

    public function test_qrcode_logs_por_pagina_tem_teto(): void
    {
        $this->logs(3);

        $resposta = $this->actingAs($this->user, 'sanctum')->getJson('/api/qrcode-logs?page=1&per_page=100000');

        $resposta->assertOk()->assertJsonPath('per_page', 100);
    }

    public function test_qrcode_logs_sem_page_mantem_lista_simples_com_teto(): void
    {
        $this->logs(3);
        config(['pagination.legacy_cap' => 2]);

        $resposta = $this->actingAs($this->user, 'sanctum')->getJson('/api/qrcode-logs');

        $resposta->assertOk()->assertJsonCount(2);
        $this->assertArrayNotHasKey('data', $resposta->json());
    }

    public function test_viagens_sem_filtro_devolvem_as_mais_recentes_em_ordem_crescente(): void
    {
        foreach (['2026-01-10', '2026-02-10', '2026-03-10'] as $data) {
            $this->viagem($data);
        }
        config(['pagination.legacy_cap' => 2]);

        $resposta = $this->actingAs($this->user, 'sanctum')->getJson('/api/trips');

        $resposta->assertOk()->assertJsonCount(2);
        $this->assertSame(
            ['2026-02-10', '2026-03-10'],
            array_map(fn ($t) => substr($t['departure_date'], 0, 10), $resposta->json())
        );
    }

    public function test_viagens_com_periodo_nao_sofrem_teto(): void
    {
        foreach (['2026-01-10', '2026-01-11', '2026-01-12'] as $data) {
            $this->viagem($data);
        }
        config(['pagination.legacy_cap' => 2]);

        $resposta = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/trips?date_begin=2026-01-01&date_end=2026-01-31');

        $resposta->assertOk()->assertJsonCount(3);
    }

    public function test_cartas_sem_page_devolvem_as_mais_recentes_dentro_do_teto(): void
    {
        foreach ([1, 2, 3] as $numero) {
            Letter::create([
                'id_user' => $this->user->id, 'number' => $numero, 'subject_matter' => "Assunto {$numero}",
                'sender' => 'Secretaria', 'recipient' => 'Diretor',
            ]);
        }
        config(['pagination.list_cap' => 2]);

        $resposta = $this->actingAs($this->user, 'sanctum')->getJson('/api/letters');

        $resposta->assertOk()->assertJsonCount(2);
        $this->assertSame([3, 2], array_column($resposta->json(), 'number'));
    }
}

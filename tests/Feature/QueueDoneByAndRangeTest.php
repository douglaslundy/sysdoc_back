<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Queue;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QueueDoneByAndRangeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Speciality $speciality;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true, 'name' => 'Admin Baixa']);
        $this->speciality = Speciality::create(['id_user' => $this->admin->id, 'name' => 'Fisioterapia']);
        $this->client = Client::create([
            'name' => 'Paciente Teste', 'mother' => 'Mae', 'cpf' => '123.456.789-00',
            'born_date' => '1990-01-01', 'active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeQueue(array $attributes = []): Queue
    {
        return Queue::create($attributes + [
            'id_client' => $this->client->id,
            'id_specialities' => $this->speciality->id,
            'id_user' => $this->admin->id,
            'done' => false,
            'urgency' => false,
        ]);
    }

    private function listIds(array $params): array
    {
        $this->app['auth']->forgetGuards();

        return collect(
            $this->actingAs($this->admin, 'sanctum')
                ->getJson('/api/queues?'.http_build_query($params + ['done' => 1]))
                ->assertOk()
                ->json('data')
        )->pluck('id')->all();
    }

    public function test_baixa_pela_api_registra_quem_deu_a_baixa(): void
    {
        $queue = $this->makeQueue();
        $other = User::factory()->create(['profile' => 'admin', 'active' => true, 'name' => 'Outro Operador']);

        $this->actingAs($other, 'sanctum')
            ->putJson("/api/queues/{$queue->id}", ['done' => true, 'date_of_realized' => '2026-05-08'])
            ->assertOk()
            ->assertJsonPath('data.done_by', $other->id)
            ->assertJsonPath('data.done_by_user.name', 'Outro Operador');

        $this->assertSame($other->id, $queue->fresh()->done_by);
    }

    public function test_listagem_traz_o_nome_de_quem_deu_baixa_e_reabrir_limpa(): void
    {
        $queue = $this->makeQueue();
        $this->actingAs($this->admin, 'sanctum')->putJson("/api/queues/{$queue->id}", ['done' => true])->assertOk();

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/queues?done=1')
            ->assertOk()
            ->assertJsonPath('data.0.done_by_user.name', 'Admin Baixa');

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')->putJson("/api/queues/{$queue->id}", ['done' => false])->assertOk();
        $this->assertNull($queue->fresh()->done_by);
    }

    public function test_filtro_por_intervalo_de_datas_considera_o_dia_inteiro_da_baixa(): void
    {
        Carbon::setTestNow('2026-05-01 00:00:00');
        $inicio = $this->makeQueue(['done' => true]);
        Carbon::setTestNow('2026-05-15 23:59:30');
        $meio = $this->makeQueue(['done' => true]);
        Carbon::setTestNow('2026-05-16 00:00:00');
        $fora = $this->makeQueue(['done' => true]);

        $this->assertEqualsCanonicalizing(
            [$inicio->id, $meio->id],
            $this->listIds(['date_from' => '2026-05-01', 'date_to' => '2026-05-15'])
        );
        $this->assertSame([$fora->id], $this->listIds(['date_from' => '2026-05-16']));
        $this->assertEqualsCanonicalizing([$inicio->id, $meio->id], $this->listIds(['date_to' => '2026-05-15']));
    }

    public function test_intervalo_so_vale_para_realizados(): void
    {
        Carbon::setTestNow('2026-05-01 10:00:00');
        $pendente = $this->makeQueue(['done' => false]);

        $this->app['auth']->forgetGuards();
        $ids = collect(
            $this->actingAs($this->admin, 'sanctum')
                ->getJson('/api/queues?done=0&date_from=2030-01-01&date_to=2030-01-31')
                ->assertOk()->json('data')
        )->pluck('id')->all();

        $this->assertSame([$pendente->id], $ids);
    }

    public function test_intervalo_invalido_e_rejeitado(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/queues?done=1&date_from=01/05/2026')
            ->assertStatus(422);

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/queues?done=1&date_from=2026-05-10&date_to=2026-05-01')
            ->assertStatus(422);
    }
}

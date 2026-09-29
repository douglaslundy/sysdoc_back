<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Queue;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QueueDoneOrderingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Speciality $speciality;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
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

    private function ids(array $params): array
    {
        $this->app['auth']->forgetGuards();

        return collect(
            $this->actingAs($this->admin, 'sanctum')
                ->getJson('/api/queues?'.http_build_query($params + ['done' => 1]))
                ->assertOk()
                ->json('data')
        )->pluck('id')->all();
    }

    public function test_baixa_registra_done_at_e_reabrir_limpa(): void
    {
        $queue = $this->makeQueue();
        $this->assertNull($queue->fresh()->done_at);

        Carbon::setTestNow('2026-05-10 14:30:00');
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queues/{$queue->id}", ['done' => true, 'date_of_realized' => '2026-05-08'])
            ->assertOk()
            ->assertJsonPath('data.done', 1);

        $this->assertSame('2026-05-10 14:30:00', $queue->fresh()->done_at->format('Y-m-d H:i:s'));

        // Editar outro campo depois nao muda a data da baixa.
        Carbon::setTestNow('2026-06-01 09:00:00');
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queues/{$queue->id}", ['obs' => 'ajuste'])
            ->assertOk();
        $this->assertSame('2026-05-10 14:30:00', $queue->fresh()->done_at->format('Y-m-d H:i:s'));

        // Reabrir limpa.
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queues/{$queue->id}", ['done' => false])
            ->assertOk();
        $this->assertNull($queue->fresh()->done_at);
    }

    public function test_listagem_devolve_done_at(): void
    {
        Carbon::setTestNow('2026-05-10 14:30:00');
        $this->makeQueue(['done' => true, 'date_of_realized' => '2026-05-08']);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/queues?done=1')
            ->assertOk()
            ->assertJsonPath('data.0.done_at', fn ($value) => str_starts_with((string) $value, '2026-05-10'));
    }

    public function test_ordena_por_data_da_baixa_realizacao_e_id_nas_duas_direcoes(): void
    {
        Carbon::setTestNow('2026-05-01 10:00:00');
        $a = $this->makeQueue(['done' => true, 'date_of_realized' => '2026-04-20']); // baixa 01/05, realizacao 20/04
        Carbon::setTestNow('2026-05-03 10:00:00');
        $b = $this->makeQueue(['done' => true, 'date_of_realized' => '2026-04-10']); // baixa 03/05, realizacao 10/04
        Carbon::setTestNow('2026-05-02 10:00:00');
        $c = $this->makeQueue(['done' => true, 'date_of_realized' => '2026-04-30']); // baixa 02/05, realizacao 30/04

        $this->assertSame([$a->id, $c->id, $b->id], $this->ids(['sort_by' => 'done_at', 'sort_dir' => 'asc']));
        $this->assertSame([$b->id, $c->id, $a->id], $this->ids(['sort_by' => 'done_at', 'sort_dir' => 'desc']));

        $this->assertSame([$b->id, $a->id, $c->id], $this->ids(['sort_by' => 'date_of_realized', 'sort_dir' => 'asc']));
        $this->assertSame([$c->id, $a->id, $b->id], $this->ids(['sort_by' => 'date_of_realized', 'sort_dir' => 'desc']));

        $this->assertSame([$a->id, $b->id, $c->id], $this->ids(['sort_by' => 'id', 'sort_dir' => 'asc']));
        $this->assertSame([$c->id, $b->id, $a->id], $this->ids(['sort_by' => 'id', 'sort_dir' => 'desc']));
    }

    public function test_sem_data_de_realizacao_vai_para_o_fim_em_qualquer_direcao(): void
    {
        Carbon::setTestNow('2026-05-01 10:00:00');
        $semData = $this->makeQueue(['done' => true, 'date_of_realized' => null]);
        $comData = $this->makeQueue(['done' => true, 'date_of_realized' => '2026-04-20']);

        $this->assertSame([$comData->id, $semData->id], $this->ids(['sort_by' => 'date_of_realized', 'sort_dir' => 'asc']));
        $this->assertSame([$comData->id, $semData->id], $this->ids(['sort_by' => 'date_of_realized', 'sort_dir' => 'desc']));
    }

    public function test_sem_parametro_de_ordenacao_mantem_a_ordem_de_chegada(): void
    {
        Carbon::setTestNow('2026-05-01 10:00:00');
        $first = $this->makeQueue(['done' => true]);
        Carbon::setTestNow('2026-05-02 10:00:00');
        $second = $this->makeQueue(['done' => true]);

        $this->assertSame([$first->id, $second->id], $this->ids([]));
    }

    public function test_ordenacao_invalida_e_rejeitada(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/queues?done=1&sort_by=password')
            ->assertStatus(422);

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/queues?done=1&sort_by=id&sort_dir=sideways')
            ->assertStatus(422);
    }
}

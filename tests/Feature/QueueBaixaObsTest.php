<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Queue;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueBaixaObsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Queue $queue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $speciality = Speciality::create(['id_user' => $this->admin->id, 'name' => 'Fisioterapia']);
        $client = Client::create([
            'name' => 'Paciente', 'mother' => 'Mae', 'cpf' => '111.222.333-44', 'born_date' => '1990-01-01', 'active' => true,
        ]);
        $this->queue = Queue::create([
            'id_client' => $client->id, 'id_specialities' => $speciality->id, 'id_user' => $this->admin->id,
            'done' => false, 'urgency' => false, 'obs' => str_repeat('a', 190),
        ]);
    }

    private function baixa(string $obs)
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($this->admin, 'sanctum')->putJson("/api/queues/{$this->queue->id}", [
            'done' => true, 'date_of_realized' => '2026-09-30', 'obs' => $obs,
        ]);
    }

    public function test_baixa_com_observacao_antiga_mais_conclusao_acima_de_200_caracteres_e_gravada(): void
    {
        // Observação de 190 caracteres + "\n" + conclusão de 100 = 291: antes falhava com 422.
        $obs = str_repeat('a', 190)."\n".str_repeat('B', 100);

        $this->baixa($obs)->assertOk()->assertJsonPath('data.done', 1);

        $this->assertSame(1, (int) $this->queue->fresh()->done);
        $this->assertSame($obs, $this->queue->fresh()->obs);
        $this->assertNotNull($this->queue->fresh()->done_at);
    }

    public function test_observacao_acima_do_novo_limite_e_rejeitada_com_mensagem_clara(): void
    {
        $this->baixa(str_repeat('x', 1001))
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains((string) $m, 'obs'));

        $this->assertSame(0, (int) $this->queue->fresh()->done);
    }
}

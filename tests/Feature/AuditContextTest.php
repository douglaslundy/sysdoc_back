<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\PedidoExame;
use App\Models\Queue;
use App\Models\Speciality;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Audit\AuditContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditContextTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(): Client
    {
        return Client::create([
            'name' => 'Cidadao Teste', 'mother' => 'Mae', 'cpf' => '111.222.333-44',
            'born_date' => '1990-01-01', 'active' => true,
        ]);
    }

    public function test_resolve_o_cidadao_de_cada_tipo_de_model(): void
    {
        $client = $this->makeClient();
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $speciality = Speciality::create(['id_user' => $admin->id, 'name' => 'Fisioterapia']);
        $queue = Queue::create([
            'id_client' => $client->id, 'id_specialities' => $speciality->id,
            'id_user' => $admin->id, 'done' => false, 'urgency' => false,
        ]);

        $this->assertSame($client->id, AuditContext::clientIdFor($client));
        $this->assertSame($client->id, AuditContext::clientIdFor($queue));
    }

    public function test_pedido_de_exame_usa_client_id(): void
    {
        $client = $this->makeClient();
        $pedido = new PedidoExame(['client_id' => $client->id]);

        $this->assertSame($client->id, AuditContext::clientIdFor($pedido));
    }

    public function test_model_sem_vinculo_com_cidadao_devolve_nulo_sem_erro(): void
    {
        $this->assertNull(AuditContext::clientIdFor(new Vehicle()));
        $this->assertNull(AuditContext::clientIdFor(null));
    }
}

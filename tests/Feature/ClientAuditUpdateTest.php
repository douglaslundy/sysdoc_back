<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAuditUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    public function test_editar_cliente_via_api_grava_audit_log_de_update(): void
    {
        $client = Client::create([
            'name' => 'Nome Original',
            'mother' => 'Mae Teste',
            'cpf' => '529.982.247-25',
            'born_date' => '1990-01-01',
            'active' => true,
        ]);

        $payload = [
            'name' => 'Nome Editado',
            'cpf' => '529.982.247-25',
            'born_date' => '1990-01-01',
            'addresses' => [
                'zip_code' => '37200-000',
                'city' => 'Cidade Teste',
                'street' => 'Rua Teste',
                'number' => '100',
                'district' => 'Bairro Teste',
            ],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/clients/{$client->id}", $payload);

        $response->assertOk();

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Nome Editado',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'UPDATE',
            'model_type' => 'Client',
            'model_id' => $client->id,
        ]);
    }
}

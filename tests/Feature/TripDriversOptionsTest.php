<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripDriversOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_perfil_tfd_ve_a_lista_de_motoristas(): void
    {
        $tfd = User::factory()->create(['profile' => 'tfd', 'active' => true]);
        $motorista = User::factory()->create(['profile' => 'user', 'is_driver' => true, 'active' => true, 'name' => 'Motorista Teste']);

        $response = $this->actingAs($tfd, 'sanctum')->getJson('/api/trips/drivers-options');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $motorista->id, 'name' => 'Motorista Teste']);
    }

    public function test_perfil_admin_ve_a_lista_de_motoristas(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $motorista = User::factory()->create(['profile' => 'user', 'is_driver' => true, 'active' => true]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/trips/drivers-options');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $motorista->id]);
    }

    public function test_lista_de_motoristas_exclui_usuarios_que_nao_sao_motoristas(): void
    {
        $tfd = User::factory()->create(['profile' => 'tfd', 'active' => true]);
        $naoMotorista = User::factory()->create(['profile' => 'user', 'is_driver' => false, 'active' => true]);

        $response = $this->actingAs($tfd, 'sanctum')->getJson('/api/trips/drivers-options');

        $response->assertOk();
        $response->assertJsonMissing(['id' => $naoMotorista->id]);
    }

    public function test_lista_de_motoristas_exclui_motoristas_inativos(): void
    {
        $tfd = User::factory()->create(['profile' => 'tfd', 'active' => true]);
        $motoristaInativo = User::factory()->create(['profile' => 'user', 'is_driver' => true, 'active' => false]);

        $response = $this->actingAs($tfd, 'sanctum')->getJson('/api/trips/drivers-options');

        $response->assertOk();
        $response->assertJsonMissing(['id' => $motoristaInativo->id]);
    }

    public function test_lista_de_motoristas_nao_expoe_dados_sensiveis(): void
    {
        $tfd = User::factory()->create(['profile' => 'tfd', 'active' => true]);
        User::factory()->create(['profile' => 'user', 'is_driver' => true, 'active' => true, 'cpf' => '11122233344']);

        $response = $this->actingAs($tfd, 'sanctum')->getJson('/api/trips/drivers-options');

        $response->assertOk();
        $response->assertJsonMissing(['cpf' => '11122233344']);
    }

    public function test_requer_autenticacao(): void
    {
        $response = $this->getJson('/api/trips/drivers-options');

        $response->assertUnauthorized();
    }
}

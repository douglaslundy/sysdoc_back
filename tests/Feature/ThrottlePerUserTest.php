<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThrottlePerUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_limite_numerico_e_por_usuario_e_nao_por_ip(): void
    {
        $a = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $b = User::factory()->create(['profile' => 'admin', 'active' => true]);

        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($a, 'sanctum')->getJson('/api/clients/select')->assertOk();
        }
        $this->actingAs($a, 'sanctum')->getJson('/api/clients/select')->assertStatus(429);

        $this->app['auth']->forgetGuards();
        $this->actingAs($b, 'sanctum')->getJson('/api/clients/select')->assertOk();
    }

    public function test_limite_geral_e_por_usuario_e_nao_por_ip(): void
    {
        $a = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $b = User::factory()->create(['profile' => 'admin', 'active' => true]);

        for ($i = 0; $i < 600; $i++) {
            $this->actingAs($a, 'sanctum')->getJson('/api/kanban?pending_only=1');
        }
        $this->actingAs($a, 'sanctum')->getJson('/api/kanban?pending_only=1')->assertStatus(429);

        $this->app['auth']->forgetGuards();
        $this->actingAs($b, 'sanctum')->getJson('/api/kanban?pending_only=1')->assertOk();
    }
}

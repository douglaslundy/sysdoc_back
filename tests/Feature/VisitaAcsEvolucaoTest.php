<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitaAcsEvolucaoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);
    }

    public function test_evolucao_requer_autenticacao(): void
    {
        $this->getJson('/api/monitor-aps/visitas/evolucao')
            ->assertStatus(401);
    }

    public function test_evolucao_rejeita_desfecho_invalido(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/monitor-aps/visitas/evolucao?desfecho=9')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['desfecho']);
    }

    public function test_evolucao_rejeita_has_geo_invalido(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/monitor-aps/visitas/evolucao?has_geo=talvez')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['has_geo']);
    }
}

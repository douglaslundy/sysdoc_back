<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitaAcsMapaBuscaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'profile' => 'admin',
            'active'  => true,
        ]);
    }

    public function test_mapa_requer_autenticacao(): void
    {
        $this->getJson('/api/monitor-aps/visitas/mapa?ano=2025&mes=1')
            ->assertStatus(401);
    }

    public function test_mapa_rejeita_ano_ausente(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/monitor-aps/visitas/mapa?mes=1')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ano']);
    }

    public function test_mapa_rejeita_mes_ausente(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/monitor-aps/visitas/mapa?ano=2025')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['mes']);
    }

    public function test_mapa_rejeita_busca_muito_longa(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/monitor-aps/visitas/mapa?ano=2025&mes=1&busca=' . str_repeat('x', 201))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['busca']);
    }
}

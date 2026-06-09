<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VisitaAcsMapaBuscaTest extends TestCase
{
    private function actingAsAdmin(): self
    {
        $user = new User();
        $user->id = 1;
        $user->profile = 'admin';
        $user->active = true;

        Sanctum::actingAs($user);
        return $this;
    }

    public function test_mapa_requer_autenticacao(): void
    {
        $this->getJson('/api/monitor-aps/visitas/mapa?ano=2025&mes=1')
            ->assertStatus(401);
    }

    public function test_mapa_rejeita_ano_ausente(): void
    {
        $this->actingAsAdmin()
            ->getJson('/api/monitor-aps/visitas/mapa?mes=1')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ano']);
    }

    public function test_mapa_rejeita_mes_ausente(): void
    {
        $this->actingAsAdmin()
            ->getJson('/api/monitor-aps/visitas/mapa?ano=2025')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['mes']);
    }

    public function test_mapa_rejeita_busca_muito_longa(): void
    {
        $this->actingAsAdmin()
            ->getJson('/api/monitor-aps/visitas/mapa?ano=2025&mes=1&busca=' . str_repeat('x', 201))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['busca']);
    }
}

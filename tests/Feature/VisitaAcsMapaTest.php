<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class VisitaAcsMapaTest extends TestCase
{
    public function test_mapa_com_busca_nao_retorna_500(): void
    {
        Cache::put('aps_db_config', null, 3600);

        $response = $this->actingAs(\App\Models\User::factory()->create())
            ->getJson('/api/monitor-aps/visitas/mapa?ano=2024&mes=1&busca=Maria');

        $this->assertNotEquals(500, $response->status());
    }

    public function test_mapa_sem_busca_nao_retorna_500(): void
    {
        Cache::put('aps_db_config', null, 3600);

        $response = $this->actingAs(\App\Models\User::factory()->create())
            ->getJson('/api/monitor-aps/visitas/mapa?ano=2024&mes=1');

        $this->assertNotEquals(500, $response->status());
    }
}

<?php
namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

class VisitaAcsMapaTest extends TestCase
{
    private function actingAsAdmin(): void
    {
        $user = new User();
        $user->id = 1;
        $user->profile = 'admin';
        $user->active = true;

        Sanctum::actingAs($user);
    }

    public function test_mapa_com_busca_nao_retorna_500(): void
    {
        Cache::put('aps_db_config', null, 3600);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/monitor-aps/visitas/mapa?ano=2024&mes=1&busca=Maria');

        $this->assertNotEquals(500, $response->status());
    }

    public function test_mapa_sem_busca_nao_retorna_500(): void
    {
        Cache::put('aps_db_config', null, 3600);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/monitor-aps/visitas/mapa?ano=2024&mes=1');

        $this->assertNotEquals(500, $response->status());
    }
}
